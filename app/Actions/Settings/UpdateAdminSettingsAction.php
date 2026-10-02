<?php

namespace App\Actions\Settings;

use App\Enums\Permission;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateAdminSettingsAction
{
    /**
     * @param  array{theme_color?: string, theme_radius?: string, theme_mode?: string}  $data
     *
     * @throws AuthorizationException
     */
    public function execute(User $user, array $data): void
    {
        if (! $user->hasPermission(Permission::ManageUsers)) {
            throw new AuthorizationException;
        }

        if (isset($data['theme_color'])) {
            SystemSetting::set('theme_color', $data['theme_color']);
        }

        if (isset($data['theme_radius'])) {
            SystemSetting::set('theme_radius', $data['theme_radius']);
        }

        if (isset($data['theme_mode'])) {
            SystemSetting::set('theme_mode', $data['theme_mode']);
        }
    }
}
