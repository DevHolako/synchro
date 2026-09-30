<?php

namespace App\Http\Requests\Programs;

use App\Enums\ProgramModality;
use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Program|null $program */
        $program = $this->route('program');

        return $program !== null && ($this->user()?->can('update', $program) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Program $program */
        $program = $this->route('program');
        $departmentId = $this->input('department_id', $program->department_id);

        return [
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('programs', 'code')
                    ->where('department_id', $departmentId)
                    ->ignore($program->id),
            ],
            'program_modality' => ['sometimes', 'required', Rule::enum(ProgramModality::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
