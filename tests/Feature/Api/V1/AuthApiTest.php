<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('active user can authenticate via login and receive bearer token', function () {
    $user = User::factory()->create([
        'email' => 'active.teacher@isga.ma',
        'password' => Hash::make('Secret123!'),
        'role' => UserRole::Teacher,
        'status' => AccountStatus::Active,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'active.teacher@isga.ma',
        'password' => 'Secret123!',
        'device_name' => 'Pixel 9 Pro',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'status',
                'permissions',
                'profile',
                'unread_notifications_count',
            ],
        ])
        ->assertJson([
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'email' => 'active.teacher@isga.ma',
                'role' => UserRole::Teacher->value,
                'status' => AccountStatus::Active->value,
            ],
        ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);
});

test('inactive or invited user cannot log in and receives 422 problem details', function () {
    User::factory()->create([
        'email' => 'invited.student@isga.ma',
        'password' => Hash::make('Secret123!'),
        'role' => UserRole::Student,
        'status' => AccountStatus::Invited,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'invited.student@isga.ma',
        'password' => 'Secret123!',
    ]);

    $response->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
            'errors',
            'invalid_params',
        ])
        ->assertJson([
            'status' => 422,
            'title' => 'Validation Failed',
            'instance' => '/api/v1/auth/login',
        ]);
});

test('invalid credentials returns 422 problem details', function () {
    User::factory()->create([
        'email' => 'active.user@isga.ma',
        'password' => Hash::make('CorrectPassword!'),
        'status' => AccountStatus::Active,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'active.user@isga.ma',
        'password' => 'WrongPassword!',
    ]);

    $response->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson([
            'status' => 422,
            'title' => 'Validation Failed',
        ]);
});

test('authenticated user can fetch their profile via auth/user', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'status' => AccountStatus::Active,
    ]);

    $token = $user->createToken('test-suite')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/user');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => UserRole::Student->value,
                'status' => AccountStatus::Active->value,
            ],
        ]);
});

test('unauthenticated request to auth/user returns 401 problem details', function () {
    $response = $this->getJson('/api/v1/auth/user');

    $response->assertStatus(401)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ])
        ->assertJson([
            'status' => 401,
            'title' => 'Unauthenticated',
        ]);
});

test('authenticated user can log out and revoke their token', function () {
    $user = User::factory()->create([
        'role' => UserRole::Teacher,
        'status' => AccountStatus::Active,
    ]);

    $plainToken = $user->createToken('logout-device')->plainTextToken;
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);

    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertOk()
        ->assertJsonStructure(['message']);

    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(0);

    // Reset guards in memory so Sanctum re-evaluates the token from the database
    app('auth')->forgetGuards();

    // Subsequent request with the same token fails with 401 RFC 7807
    $userResponse = $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->getJson('/api/v1/auth/user');

    $userResponse->assertStatus(401)
        ->assertHeader('Content-Type', 'application/problem+json');
});
