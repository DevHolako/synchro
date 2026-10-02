<?php

namespace App\Http\Requests\AdminSettings;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageUsers) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'theme_color' => ['required', 'string', Rule::in(['indigo', 'ocean', 'emerald', 'violet', 'rose', 'amber', 'zinc'])],
            'theme_radius' => ['required', 'string', Rule::in(['sm', 'md', 'lg'])],
            'theme_mode' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
        ];
    }
}
