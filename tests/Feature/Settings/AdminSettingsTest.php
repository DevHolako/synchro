<?php

use App\Enums\Permission;
use App\Models\SystemSetting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users cannot view admin settings', function () {
    $this->get(route('admin.settings.index'))
        ->assertRedirect(route('login'));
});

test('unauthenticated users cannot update admin settings', function () {
    $this->put(route('admin.settings.update'), [
        'theme_color' => 'ocean',
        'theme_radius' => 'lg',
        'theme_mode' => 'dark',
    ])->assertRedirect(route('login'));
});

test('users without ManageUsers permission cannot view admin settings', function () {
    $user = User::factory()->create(); // Student by default, no ManageUsers

    expect($user->hasPermission(Permission::ManageUsers))->toBeFalse();

    $this->actingAs($user)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});

test('users without ManageUsers permission cannot update admin settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('admin.settings.update'), [
            'theme_color' => 'ocean',
            'theme_radius' => 'lg',
            'theme_mode' => 'dark',
        ])
        ->assertForbidden();
});

test('administrators can view admin settings page with available options', function () {
    $admin = User::factory()->admin()->create();

    SystemSetting::set('theme_color', 'ocean');
    SystemSetting::set('theme_radius', 'lg');
    SystemSetting::set('theme_mode', 'dark');

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/settings/index')
            ->has('settings', fn (Assert $settings) => $settings
                ->where('theme_color', 'ocean')
                ->where('theme_radius', 'lg')
                ->where('theme_mode', 'dark')
            )
            ->has('availableColors', 7)
            ->has('availableRadii', 3)
            ->has('availableModes', 3)
        );
});

test('administrators can update appearance settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'theme_color' => 'emerald',
            'theme_radius' => 'sm',
            'theme_mode' => 'light',
        ])
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('toast.type', 'success');

    expect(SystemSetting::get('theme_color'))->toBe('emerald')
        ->and(SystemSetting::get('theme_radius'))->toBe('sm')
        ->and(SystemSetting::get('theme_mode'))->toBe('light');
});

test('updating settings validates color, radius, and mode values', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'theme_color' => 'invalid-neon',
            'theme_radius' => 'huge',
            'theme_mode' => 'cyberpunk',
        ])
        ->assertSessionHasErrors(['theme_color', 'theme_radius', 'theme_mode']);
});

test('shared inertia props include theme across pages', function () {
    $user = User::factory()->create();

    SystemSetting::set('theme_color', 'violet');
    SystemSetting::set('theme_radius', 'lg');
    SystemSetting::set('theme_mode', 'system');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('theme', fn (Assert $theme) => $theme
                ->where('color', 'violet')
                ->where('radius', 'lg')
                ->where('mode', 'system')
            )
        );
});
