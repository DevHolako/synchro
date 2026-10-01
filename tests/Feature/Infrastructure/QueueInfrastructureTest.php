<?php

use App\Enums\UserRole;
use App\Models\InvitationToken;
use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

test('invitation emails are queued on the notifications queue', function () {
    Queue::fake();

    $this->actingAs(User::factory()->admin()->create())->post(route('users.store'), [
        'name' => 'Queued Teacher',
        'email' => 'queued@isga.ma',
        'role' => UserRole::Teacher->value,
    ])->assertSessionHasNoErrors();

    Queue::assertPushedOn('notifications', SendQueuedNotifications::class);
});

test('the horizon dashboard requires the monitor queues permission', function () {
    $this->get('/horizon')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->coordinator()->create())->get('/horizon')->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())->get('/horizon')->assertOk();
});

test('queue housekeeping commands are scheduled', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn (Event $event) => $event->command)
        ->implode("\n");

    expect($commands)
        ->toContain('horizon:snapshot')
        ->toContain('queue:prune-failed')
        ->toContain('model:prune')
        ->toContain('imports:fail-stale');
});

test('unusable invitation tokens past the retention window are pruned', function () {
    $user = User::factory()->create();
    $stale = InvitationToken::factory()->for($user)->create(['expires_at' => now()->subDays(InvitationToken::RETENTION_DAYS + 2)]);
    $pending = InvitationToken::factory()->for($user)->create();

    $this->artisan('model:prune', ['--model' => [InvitationToken::class]])->assertSuccessful();

    expect(InvitationToken::find($stale->id))->toBeNull()
        ->and(InvitationToken::find($pending->id))->not->toBeNull();
});

test('a user\'s latest invitation is kept so the directory still shows it expired', function () {
    $user = User::factory()->create();
    InvitationToken::factory()->for($user)->create(['expires_at' => now()->subDays(InvitationToken::RETENTION_DAYS + 2)]);
    $latest = InvitationToken::factory()->for($user)->create(['expires_at' => now()->subDays(InvitationToken::RETENTION_DAYS + 1)]);

    $this->artisan('model:prune', ['--model' => [InvitationToken::class]])->assertSuccessful();

    expect($user->invitationTokens()->pluck('id')->all())->toBe([$latest->id]);
});
