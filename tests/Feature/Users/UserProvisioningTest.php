<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\InvitationToken;
use App\Models\StudentGroup;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Support\SessionKey;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

test('provisioning a teacher creates an invited account, teacher profile, and emailed invitation', function () {
    $department = Department::factory()->create();

    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Amina Teacher',
        'email' => 'Amina.Teacher@Example.com',
        'role' => UserRole::Teacher->value,
        'teacher_profile' => [
            'department_id' => $department->id,
            'employee_number' => 'ENS-00042',
            'phone' => '0612345678',
        ],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $user = User::where('email', 'amina.teacher@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Teacher)
        ->and($user->status)->toBe(AccountStatus::Invited)
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->teacherProfile->department_id)->toBe($department->id)
        ->and($user->teacherProfile->employee_number)->toBe('ENS-00042')
        ->and($user->studentProfile)->toBeNull();

    $invitation = $user->invitationTokens()->sole();

    expect($invitation->invited_by)->toBe($this->admin->id)
        ->and($invitation->expires_at->diffInHours(now(), absolute: true))->toEqualWithDelta(72, 0.01);

    Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) {
        return str_contains($notification->activationUrl, '/invitations/')
            && str_contains($notification->activationUrl, 'signature=')
            && str_contains($notification->activationUrl, 'expires=');
    });
});

test('provisioning a student creates a student profile linked to the group', function () {
    $group = StudentGroup::factory()->create();

    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Youssef Student',
        'email' => 'youssef@example.com',
        'role' => UserRole::Student->value,
        'student_profile' => [
            'student_group_id' => $group->id,
            'student_number' => 'ETU-123456',
        ],
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'youssef@example.com')->firstOrFail();

    expect($user->studentProfile->student_group_id)->toBe($group->id)
        ->and($user->studentProfile->student_number)->toBe('ETU-123456')
        ->and($user->teacherProfile)->toBeNull();
});

test('provisioning a coordinator creates no role profile', function () {
    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Karim Coordinator',
        'email' => 'karim@example.com',
        'role' => UserRole::Coordinator->value,
        'teacher_profile' => ['employee_number' => 'IGNORED-1'],
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'karim@example.com')->firstOrFail();

    expect($user->teacherProfile)->toBeNull()
        ->and($user->studentProfile)->toBeNull();
});

test('provisioning validates email uniqueness, role, and profile references', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => '',
        'email' => 'taken@example.com',
        'role' => 'superuser',
        'student_profile' => ['student_group_id' => 9999],
    ])->assertSessionHasErrors(['name', 'email', 'role', 'student_profile.student_group_id']);

    Notification::assertNothingSent();
});

test('resending an invitation revokes previous tokens and sends a fresh link', function () {
    $user = User::factory()->invited()->create();
    $previous = InvitationToken::factory()->for($user)->create();

    $this->actingAs($this->admin)
        ->post(route('users.resend-invitation', $user))
        ->assertRedirect();

    expect($previous->fresh()->revoked_at)->not->toBeNull()
        ->and($user->invitationTokens()->pending()->count())->toBe(1);

    Notification::assertSentToTimes($user, UserInvitationNotification::class, 1);
});

test('resending an invitation to an active account is refused', function () {
    $user = User::factory()->teacher()->create();

    $this->actingAs($this->admin)
        ->post(route('users.resend-invitation', $user))
        ->assertRedirect();

    expect($user->invitationTokens()->count())->toBe(0);
    Notification::assertNothingSent();
});

test('issuing a temporary password activates the account and revokes pending invitations', function () {
    $user = User::factory()->invited()->create();
    $invitation = InvitationToken::factory()->for($user)->create();

    $response = $this->actingAs($this->admin)->post(route('users.temporary-password', $user));

    $response->assertRedirect();
    $flash = session(SessionKey::FLASH_DATA) ?? [];
    $password = data_get($flash, 'temporary_password.password');

    $user->refresh();

    expect($password)->toBeString()->toHaveLength(16)
        ->and($user->status)->toBe(AccountStatus::Active)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($invitation->fresh()->revoked_at)->not->toBeNull();

    auth()->logout();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => $password,
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});
