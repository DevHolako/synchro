<?php

use App\Actions\Exams\SendUrgentMessageAction;
use App\Exceptions\UrgentAlertDeliveryException;
use App\Jobs\SendUrgentMessageJob;
use App\Models\UrgentAlert;
use App\Services\UrgentMessages\Drivers\DatabaseDriver;
use App\Services\UrgentMessages\Drivers\LogDriver;
use App\Services\UrgentMessages\Drivers\TwilioDriver;
use App\Services\UrgentMessages\Drivers\WhatsAppDriver;
use App\Services\UrgentMessages\UrgentAlertGatewayInterface;
use App\Services\UrgentMessages\UrgentAlertManager;
use App\Services\UrgentMessages\UrgentMessageGateway;
use App\Support\PhoneNumber;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('it resolves default driver and custom drivers via manager', function () {
    config(['services.urgent_messages.driver' => 'log']);

    $manager = app(UrgentAlertManager::class);

    expect($manager->getDefaultDriver())->toBe('log')
        ->and($manager->driver())->toBeInstanceOf(LogDriver::class)
        ->and($manager->driver('database'))->toBeInstanceOf(DatabaseDriver::class)
        ->and($manager->driver('twilio'))->toBeInstanceOf(TwilioDriver::class)
        ->and($manager->driver('whatsapp'))->toBeInstanceOf(WhatsAppDriver::class);

    expect(app(UrgentMessageGateway::class))->toBeInstanceOf(UrgentAlertManager::class)
        ->and(app(UrgentAlertGatewayInterface::class))->toBeInstanceOf(UrgentAlertManager::class);

    expect($manager->sendUrgentAlert('0612345678', 'Test message'))->toBeTrue();
});

test('it normalizes moroccan and international phone numbers', function () {
    expect(PhoneNumber::normalize('0612345678'))->toBe('+212612345678')
        ->and(PhoneNumber::normalize('07 98 76 54 32'))->toBe('+212798765432')
        ->and(PhoneNumber::normalize('0522-123456'))->toBe('+212522123456')
        ->and(PhoneNumber::normalize('+212612345678'))->toBe('+212612345678')
        ->and(PhoneNumber::normalize('00212612345678'))->toBe('+212612345678')
        ->and(PhoneNumber::normalize('+33612345678'))->toBe('+33612345678');
});

test('it logs urgent alert with normalized phone and metadata via log driver', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Urgent message', [
            'phone' => '+212612345678',
            'message' => 'Test urgent announcement',
            'metadata' => ['exam_id' => 42],
        ]);

    config(['services.urgent_messages.driver' => 'log']);
    $manager = app(UrgentAlertManager::class);

    $manager->send('0612345678', 'Test urgent announcement', ['exam_id' => 42]);
});

test('it stores alert in database table via database driver', function () {
    config(['services.urgent_messages.driver' => 'database']);
    $manager = app(UrgentAlertManager::class);

    $manager->send('0699887766', 'Urgent schedule change', ['module' => 'Algorithmique']);

    $alert = UrgentAlert::query()->where('phone', '+212699887766')->first();

    expect($alert)->not->toBeNull()
        ->and($alert->message)->toBe('Urgent schedule change')
        ->and($alert->driver)->toBe('database')
        ->and($alert->status)->toBe('sent')
        ->and($alert->metadata)->toBe(['module' => 'Algorithmique']);
});

test('it dispatches SMS via twilio driver with valid credentials and payload', function () {
    config([
        'services.twilio.account_sid' => 'AC_TEST_SID',
        'services.twilio.auth_token' => 'SECRET_AUTH_TOKEN',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json' => Http::response([
            'sid' => 'SM123456',
            'status' => 'queued',
        ], 201),
    ]);

    $driver = app(UrgentAlertManager::class)->driver('twilio');
    $driver->send('+212612345678', 'Salle modifiée vers B201');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json'
            && $request['To'] === '+212612345678'
            && $request['From'] === '+15551234567'
            && $request['Body'] === 'Salle modifiée vers B201'
            && $request->hasHeader('Authorization');
    });
});

test('it throws delivery exception when twilio credentials are missing', function () {
    config([
        'services.twilio.account_sid' => '',
        'services.twilio.auth_token' => '',
        'services.twilio.from' => '',
    ]);

    $driver = app(UrgentAlertManager::class)->driver('twilio');

    expect(fn () => $driver->send('+212612345678', 'Hello'))
        ->toThrow(UrgentAlertDeliveryException::class, 'Twilio credentials are not configured.');
});

test('it throws delivery exception when twilio request fails', function () {
    config([
        'services.twilio.account_sid' => 'AC_TEST_SID',
        'services.twilio.auth_token' => 'SECRET_TOKEN',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json' => Http::response([
            'code' => 21211,
            'message' => 'The To phone number is not valid',
        ], 400),
    ]);

    $driver = app(UrgentAlertManager::class)->driver('twilio');

    expect(fn () => $driver->send('+212612345678', 'Hello'))
        ->toThrow(UrgentAlertDeliveryException::class, 'Twilio SMS delivery failed: [400]');
});

test('it dispatches message via whatsapp driver with valid credentials and payload', function () {
    config([
        'services.whatsapp.token' => 'WHATSAPP_BEARER_TOKEN',
        'services.whatsapp.phone_number_id' => '109876543210',
    ]);

    Http::fake([
        'https://graph.facebook.com/v20.0/109876543210/messages' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.HBg...']],
        ], 200),
    ]);

    $driver = app(UrgentAlertManager::class)->driver('whatsapp');
    $driver->send('+212612345678', 'Exam reporté à 14h00');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://graph.facebook.com/v20.0/109876543210/messages'
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '212612345678'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Exam reporté à 14h00'
            && $request->hasHeader('Authorization', 'Bearer WHATSAPP_BEARER_TOKEN');
    });
});

test('it throws delivery exception when whatsapp credentials are missing', function () {
    config([
        'services.whatsapp.token' => '',
        'services.whatsapp.phone_number_id' => '',
    ]);

    $driver = app(UrgentAlertManager::class)->driver('whatsapp');

    expect(fn () => $driver->send('+212612345678', 'Hello'))
        ->toThrow(UrgentAlertDeliveryException::class, 'WhatsApp credentials are not configured.');
});

test('it throws delivery exception when whatsapp request fails', function () {
    config([
        'services.whatsapp.token' => 'TOKEN',
        'services.whatsapp.phone_number_id' => '109876543210',
    ]);

    Http::fake([
        'https://graph.facebook.com/v20.0/109876543210/messages' => Http::response([
            'error' => ['message' => 'Rate limit hit', 'code' => 130429],
        ], 429),
    ]);

    $driver = app(UrgentAlertManager::class)->driver('whatsapp');

    expect(fn () => $driver->send('+212612345678', 'Hello'))
        ->toThrow(UrgentAlertDeliveryException::class, 'WhatsApp delivery failed: [429]');
});

test('it integrates with SendUrgentMessageAction and SendUrgentMessageJob', function () {
    config(['services.urgent_messages.driver' => 'database']);

    $job = new SendUrgentMessageJob(
        key: 'exam:99:rev:1:user:45',
        phone: '0611223344',
        message: 'Alerte météo: séance décalée',
    );

    app()->call([$job, 'handle']);

    $alert = UrgentAlert::query()->where('phone', '+212611223344')->first();

    expect($alert)->not->toBeNull()
        ->and($alert->message)->toBe('Alerte météo: séance décalée');

    // Duplicate delivery is a no-op due to idempotent caching
    $action = app(SendUrgentMessageAction::class);
    $result = $action->execute('exam:99:rev:1:user:45', '0611223344', 'Alerte météo: séance décalée');

    expect($result)->toBeFalse()
        ->and(UrgentAlert::query()->where('phone', '+212611223344')->count())->toBe(1);
});
