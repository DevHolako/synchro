<?php

namespace App\Actions\Imports\Importers;

use App\Actions\Users\ProvisionUserAction;
use App\Enums\UserRole;
use App\Exceptions\ImportRowException;
use App\Models\Department;
use App\Models\TeacherProfile;
use App\Models\User;

/**
 * @implements RowImporter<array{name: string, email: string, role: UserRole, teacher_profile: array{department_id: int|null, employee_number: string|null, phone: string|null}}>
 */
class TeacherRowImporter implements RowImporter
{
    public function __construct(private readonly ProvisionUserAction $provisionUser) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'department_code' => ['nullable', 'string', 'max:50'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function uniqueKeys(array $row): array
    {
        return array_filter([
            'email' => mb_strtolower((string) ($row['email'] ?? '')),
            'employee_number' => mb_strtoupper((string) ($row['employee_number'] ?? '')),
        ]);
    }

    public function prepare(array $row): array
    {
        $email = mb_strtolower((string) $row['email']);

        if (User::query()->where('email', $email)->exists()) {
            throw new ImportRowException('email', __('messages.import_email_exists', ['email' => $email]));
        }

        $departmentId = null;

        if (! empty($row['department_code'])) {
            $departmentId = Department::query()->where('code', $row['department_code'])->value('id');

            if ($departmentId === null) {
                throw new ImportRowException('department_code', __('messages.import_department_not_found', ['code' => $row['department_code']]));
            }
        }

        if (! empty($row['employee_number']) && TeacherProfile::query()->where('employee_number', $row['employee_number'])->exists()) {
            throw new ImportRowException('employee_number', __('messages.import_employee_number_exists', ['number' => $row['employee_number']]));
        }

        return [
            'name' => $row['name'],
            'email' => $email,
            'role' => UserRole::Teacher,
            'teacher_profile' => [
                'department_id' => $departmentId,
                'employee_number' => $row['employee_number'] ?? null,
                'phone' => $row['phone'] ?? null,
            ],
        ];
    }

    public function persist(array $payload, User $actor): void
    {
        $this->provisionUser->execute($payload, $actor);
    }
}
