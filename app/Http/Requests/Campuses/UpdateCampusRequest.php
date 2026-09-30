<?php

namespace App\Http\Requests\Campuses;

use App\Models\Campus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Campus $campus */
        $campus = $this->route('campus');

        return $this->user()?->can('update', $campus) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Campus $campus */
        $campus = $this->route('campus');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('campuses', 'code')->ignore($campus->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
