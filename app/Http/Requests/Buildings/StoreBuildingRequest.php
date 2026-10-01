<?php

namespace App\Http\Requests\Buildings;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildingRequest extends FormRequest
{
    use ReadsTypedInput;

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

    /**
     * The validated input, typed for the action.
     *
     * @return array{campus_id: int, name: string, code: string|null, is_active?: bool}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'campus_id' => $this->integer('campus_id'),
            'name' => $this->string('name')->value(),
            'code' => $this->nullableString('code'),
            ...$this->activeFlag(),
        ];
    }
}
