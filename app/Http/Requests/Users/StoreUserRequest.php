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
            // Students are named by their official given name and surname instead.
            'name' => ['exclude_if:role,student', 'required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::enum(UserRole::class)],

            'teacher_profile' => ['nullable', 'array'],
            'teacher_profile.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'teacher_profile.employee_number' => ['nullable', 'string', 'max:50', 'unique:teacher_profiles,employee_number'],
            'teacher_profile.phone' => ['nullable', 'string', 'max:30'],

            'student_profile' => ['nullable', 'array', 'required_if:role,student'],
            'student_profile.last_name' => ['exclude_unless:role,student', 'required', 'string', 'max:100'],
            'student_profile.first_name' => ['exclude_unless:role,student', 'required', 'string', 'max:100'],
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
     * @return array{name: string|null, email: string, role: string, teacher_profile: array{department_id: int|null, employee_number: string|null, phone: string|null}|null, student_profile: array{last_name?: string, first_name?: string, student_group_id: int|null, student_number: string|null, phone: string|null}|null}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'name' => $this->nullableString('name'),
            'email' => $this->string('email')->value(),
            'role' => $this->string('role')->value(),
            'teacher_profile' => $this->filled('teacher_profile') ? [
                'department_id' => $this->nullableInteger('teacher_profile.department_id'),
                'employee_number' => $this->nullableString('teacher_profile.employee_number'),
                'phone' => $this->nullableString('teacher_profile.phone'),
            ] : null,
            'student_profile' => $this->filled('student_profile') ? [
                ...($this->input('role') === UserRole::Student->value ? [
                    'last_name' => $this->string('student_profile.last_name')->trim()->value(),
                    'first_name' => $this->string('student_profile.first_name')->trim()->value(),
                ] : []),
                'student_group_id' => $this->nullableInteger('student_profile.student_group_id'),
                'student_number' => $this->nullableString('student_profile.student_number'),
                'phone' => $this->nullableString('student_profile.phone'),
            ] : null,
        ];
    }
}
