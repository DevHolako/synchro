<?php

use App\Actions\Invitations\IssueInvitationAction;
use App\Enums\AccountStatus;
use App\Models\InvitationToken;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->withoutVite();

    $this->invitee = User::factory()->invited()->teacher()->create();
    $this->invitation = app(IssueInvitationAction::class)->execute($this->invitee);

    Notification::assertSentTo($this->invitee, UserInvitationNotification::class, function (UserInvitationNotification $notification) {
        $this->activationUrl = $notification->activationUrl;

        return true;
    });

});

function activationPayload(): array
{
    return ['password' => 'N3w-Secure-Passw0rd!', 'password_confirmation' => 'N3w-Secure-Passw0rd!'];
}

test('a valid invitation link renders the password setup screen', function () {
    $this->get($this->activationUrl)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/accept-invitation')
            ->where('email', $this->invitee->email)
            ->has('activateUrl')
            ->has('expiresAt'));
});

test('a valid invitation activates the account, consumes the token, and logs the user in', function () {
    $this->post($this->activationUrl, activationPayload())
        ->assertRedirect(route('dashboard'));

    $this->invitee->refresh();

    expect($this->invitee->status)->toBe(AccountStatus::Active)
        ->and($this->invitee->activated_at)->not->toBeNull()
        ->and($this->invitee->email_verified_at)->not->toBeNull()
        ->and($this->invitation->fresh()->consumed_at)->not->toBeNull();

    $this->assertAuthenticatedAs($this->invitee);
    $this->get(route('dashboard'))->assertOk();
});

test('an invitation cannot be used twice', function () {
    $this->post($this->activationUrl, activationPayload())->assertRedirect(route('dashboard'));
    auth()->logout();

    $this->get($this->activationUrl)
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('auth/invitation-invalid'));

    $this->post($this->activationUrl, activationPayload())->assertForbidden();
});

test('invitations older than 72 hours are rejected', function () {
    $this->travel(InvitationToken::LIFETIME_HOURS + 1)->hours();

    $this->get($this->activationUrl)
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('auth/invitation-invalid'));

    $this->post($this->activationUrl, activationPayload())->assertForbidden();

    expect($this->invitee->fresh()->status)->toBe(AccountStatus::Invited);
    $this->assertGuest();
});

test('invitations remain valid just before the 72 hour window closes', function () {
    $this->travel(InvitationToken::LIFETIME_HOURS - 1)->hours();

    $this->get($this->activationUrl)->assertOk();
});

test('tampered invitation signatures are rejected', function () {
    $tampered = preg_replace('/signature=[a-f0-9]+/', 'signature='.str_repeat('0', 64), $this->activationUrl);

    $this->get($tampered)->assertForbidden();
    $this->post($tampered, activationPayload())->assertForbidden();

    expect($this->invitee->fresh()->status)->toBe(AccountStatus::Invited);
});

test('an unsigned invitation link is rejected', function () {
    $unsigned = strtok($this->activationUrl, '?');

    $this->get($unsigned)->assertForbidden();
    $this->post($unsigned, activationPayload())->assertForbidden();
});

test('a correctly signed link for an unknown token is rejected', function () {
    $forged = URL::temporarySignedRoute('invitations.activate', now()->addHour(), ['token' => str_repeat('x', 64)]);

    $this->post($forged, activationPayload())->assertForbidden();

    expect($this->invitee->fresh()->status)->toBe(AccountStatus::Invited);
});

test('a revoked invitation link stops working after a resend', function () {
    app(IssueInvitationAction::class)->execute($this->invitee);

    $this->get($this->activationUrl)->assertForbidden();
    $this->post($this->activationUrl, activationPayload())->assertForbidden();
});

test('password confirmation is required to activate', function () {
    $this->post($this->activationUrl, [
        'password' => 'N3w-Secure-Passw0rd!',
        'password_confirmation' => 'mismatch',
    ])->assertSessionHasErrors('password');

    expect($this->invitee->fresh()->status)->toBe(AccountStatus::Invited);
});

test('invited users cannot sign in before accepting their invitation', function () {
    $this->invitee->forceFill(['password' => 'known-password'])->save();

    $this->post(route('login.store'), [
        'email' => $this->invitee->email,
        'password' => 'known-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
