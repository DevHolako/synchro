<?php

namespace App\Http\Requests\Buildings;

use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Building $building */
        $building = $this->route('building');

        return $this->user()?->can('update', $building) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Building $building */
        $building = $this->route('building');
        $campusId = $this->input('campus_id', $building->campus_id);

        return [
            'campus_id' => ['sometimes', 'required', 'integer', 'exists:campuses,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('buildings', 'name')
                    ->where('campus_id', $campusId)
                    ->ignore($building->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }
}
