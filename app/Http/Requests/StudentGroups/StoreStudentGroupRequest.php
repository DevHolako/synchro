<?php

namespace App\Http\Requests\StudentGroups;

use App\Models\StudentGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StudentGroup::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('student_groups', 'name')
                    ->where('program_id', $this->input('program_id'))
                    ->where('academic_year', $this->input('academic_year')),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
            'expected_headcount' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
