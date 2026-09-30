<?php

namespace App\Http\Requests\Buildings;

use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Building::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('buildings', 'name')->where('campus_id', $this->input('campus_id')),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }
}
