<?php

namespace App\Http\Requests\Programs;

use App\Enums\ProgramModality;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Program::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('programs', 'code')->where('department_id', $this->input('department_id')),
            ],
            'program_modality' => ['required', Rule::enum(ProgramModality::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The validated input, typed for the action.
     *
     * @return array{department_id: int, name: string, code: string, program_modality: string, description: string|null, is_active?: bool}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'department_id' => $this->integer('department_id'),
            'name' => $this->string('name')->value(),
            'code' => $this->string('code')->value(),
            'program_modality' => $this->string('program_modality')->value(),
            'description' => $this->nullableString('description'),
            ...$this->activeFlag(),
        ];
    }
}
