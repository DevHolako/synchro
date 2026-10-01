<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->withoutVite();
});

test('public self-registration routes are disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::where('email', 'intruder@example.com')->exists())->toBeFalse();
});

test('guests are redirected away from user provisioning', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->post(route('users.store'), [])->assertRedirect(route('login'));
});

test('administrators can list and provision users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('users.index'))->assertOk();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Teacher',
        'email' => 'new.teacher@example.com',
        'role' => 'teacher',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'new.teacher@example.com')->exists())->toBeTrue();
});

test('users without user permissions cannot access provisioning', function (string $state) {
    $actor = User::factory()->{$state}()->create();
    $target = User::factory()->invited()->create();

    $this->actingAs($actor)->get(route('users.index'))->assertForbidden();

    $this->actingAs($actor)->post(route('users.store'), [
        'name' => 'Sneaky User',
        'email' => 'sneaky@example.com',
        'role' => 'administrator',
    ])->assertForbidden();

    $this->actingAs($actor)->post(route('users.resend-invitation', $target))->assertForbidden();
    $this->actingAs($actor)->post(route('users.temporary-password', $target))->assertForbidden();

    expect(User::where('email', 'sneaky@example.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
})->with(['coordinator', 'teacher', 'student']);

test('administrators cannot issue a temporary password for their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('users.temporary-password', $admin))->assertForbidden();
});
