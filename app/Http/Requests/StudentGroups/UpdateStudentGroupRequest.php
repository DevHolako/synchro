<?php

namespace App\Http\Requests\StudentGroups;

use App\Models\StudentGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var StudentGroup|null $studentGroup */
        $studentGroup = $this->route('student_group');

        return $studentGroup !== null && ($this->user()?->can('update', $studentGroup) ?? false);
    }

    /**
     * Validate scoped uniqueness and cross-field invariants against the stored values
     * when a partial update omits them (e.g. moving a record to another parent).
     */
    protected function prepareForValidation(): void
    {
        /** @var StudentGroup $studentGroup */
        $studentGroup = $this->route('student_group');

        $this->mergeIfMissing([
            'name' => $studentGroup->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var StudentGroup $studentGroup */
        $studentGroup = $this->route('student_group');
        $programId = $this->input('program_id', $studentGroup->program_id);
        $academicYear = $this->input('academic_year', $studentGroup->academic_year);

        return [
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('student_groups', 'name')
                    ->where('program_id', $programId)
                    ->where('academic_year', $academicYear)
                    ->ignore($studentGroup->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'academic_year' => ['sometimes', 'required', 'string', 'max:20'],
            'expected_headcount' => ['sometimes', 'required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
