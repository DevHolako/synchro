<?php

namespace App\Actions\Settings;

use App\Enums\Permission;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class GetAdminSettingsAction
{
    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function execute(User $user): array
    {
        if (! $user->hasPermission(Permission::ManageUsers)) {
            throw new AuthorizationException;
        }

        return [
            'settings' => [
                'theme_color' => SystemSetting::get('theme_color', 'indigo'),
                'theme_radius' => SystemSetting::get('theme_radius', 'md'),
                'theme_mode' => SystemSetting::get('theme_mode', 'system'),
            ],
            'availableColors' => [
                ['id' => 'indigo', 'name' => 'Indigo', 'hex' => '#4F46E5', 'description' => 'Vibrant Academic & Modern Tech'],
                ['id' => 'ocean', 'name' => 'Ocean', 'hex' => '#2563EB', 'description' => 'Classic Blue & Trustworthy'],
                ['id' => 'emerald', 'name' => 'Emerald', 'hex' => '#059669', 'description' => 'Fresh & Focused Mint'],
                ['id' => 'violet', 'name' => 'Violet', 'hex' => '#7C3AED', 'description' => 'Royal & Creative'],
                ['id' => 'rose', 'name' => 'Rose', 'hex' => '#E11D48', 'description' => 'Warm Ruby & Energetic'],
                ['id' => 'amber', 'name' => 'Amber', 'hex' => '#D97706', 'description' => 'Autumn Gold & Welcoming'],
                ['id' => 'zinc', 'name' => 'Zinc', 'hex' => '#52525B', 'description' => 'Classic Neutral Monochrome'],
            ],
            'availableRadii' => [
                ['id' => 'sm', 'name' => 'Compact (6px)', 'value' => '0.375rem'],
                ['id' => 'md', 'name' => 'Standard (10px)', 'value' => '0.625rem'],
                ['id' => 'lg', 'name' => 'Rounded (14px)', 'value' => '0.875rem'],
            ],
            'availableModes' => [
                ['id' => 'light', 'name' => 'Light'],
                ['id' => 'dark', 'name' => 'Dark'],
                ['id' => 'system', 'name' => 'System'],
            ],
        ];
    }
}
