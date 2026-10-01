<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::enum(UserRole::class)],

            'teacher_profile' => ['nullable', 'array'],
            'teacher_profile.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'teacher_profile.employee_number' => ['nullable', 'string', 'max:50', 'unique:teacher_profiles,employee_number'],
            'teacher_profile.phone' => ['nullable', 'string', 'max:30'],

            'student_profile' => ['nullable', 'array'],
            'student_profile.student_group_id' => ['nullable', 'integer', 'exists:student_groups,id'],
            'student_profile.student_number' => ['nullable', 'string', 'max:50', 'unique:student_profiles,student_number'],
            'student_profile.phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * The validated input, typed for the action.
     *
     * @return array{name: string, email: string, role: string, teacher_profile: array{department_id: int|null, employee_number: string|null, phone: string|null}|null, student_profile: array{student_group_id: int|null, student_number: string|null, phone: string|null}|null}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'name' => $this->string('name')->value(),
            'email' => $this->string('email')->value(),
            'role' => $this->string('role')->value(),
            'teacher_profile' => $this->filled('teacher_profile') ? [
                'department_id' => $this->nullableInteger('teacher_profile.department_id'),
                'employee_number' => $this->nullableString('teacher_profile.employee_number'),
                'phone' => $this->nullableString('teacher_profile.phone'),
            ] : null,
            'student_profile' => $this->filled('student_profile') ? [
                'student_group_id' => $this->nullableInteger('student_profile.student_group_id'),
                'student_number' => $this->nullableString('student_profile.student_number'),
                'phone' => $this->nullableString('student_profile.phone'),
            ] : null,
        ];
    }
}
