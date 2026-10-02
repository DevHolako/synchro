<?php

namespace App\Services\UrgentMessages;

use App\Services\UrgentMessages\Drivers\DatabaseDriver;
use App\Services\UrgentMessages\Drivers\LogDriver;
use App\Services\UrgentMessages\Drivers\TwilioDriver;
use App\Services\UrgentMessages\Drivers\WhatsAppDriver;
use App\Support\PhoneNumber;
use Illuminate\Support\Manager;

/**
 * Manager for emergency notification gateway supporting multiple swappable drivers (ADR 0003).
 */
class UrgentAlertManager extends Manager implements UrgentAlertGatewayInterface
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('services.urgent_messages.driver', 'log');
    }

    public function createLogDriver(): LogDriver
    {
        return new LogDriver;
    }

    public function createDatabaseDriver(): DatabaseDriver
    {
        return new DatabaseDriver;
    }

    public function createTwilioDriver(): TwilioDriver
    {
        return new TwilioDriver(
            (string) $this->config->get('services.twilio.account_sid'),
            (string) $this->config->get('services.twilio.auth_token'),
            (string) $this->config->get('services.twilio.from'),
        );
    }

    public function createWhatsappDriver(): WhatsAppDriver
    {
        return new WhatsAppDriver(
            (string) $this->config->get('services.whatsapp.token'),
            (string) $this->config->get('services.whatsapp.phone_number_id'),
        );
    }

    /**
     * Normalizes the recipient phone number and sends via the resolved driver.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function send(string $phone, string $message, array $metadata = []): void
    {
        $normalizedPhone = PhoneNumber::normalize($phone);

        /** @var UrgentMessageGateway $driver */
        $driver = $this->driver();

        $driver->send($normalizedPhone, $message, $metadata);
    }
}
