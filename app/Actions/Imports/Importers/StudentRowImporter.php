<?php

namespace App\Actions\Imports\Importers;

use App\Actions\Users\ProvisionUserAction;
use App\Enums\UserRole;
use App\Exceptions\ImportRowException;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;

class StudentRowImporter implements RowImporter
{
    public function __construct(private readonly ProvisionUserAction $provisionUser) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'group_code' => ['required', 'string', 'max:50'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function uniqueKeys(array $row): array
    {
        return array_filter([
            'email' => mb_strtolower((string) ($row['email'] ?? '')),
            'student_number' => mb_strtoupper((string) ($row['student_number'] ?? '')),
        ]);
    }

    public function prepare(array $row): array
    {
        $email = mb_strtolower((string) $row['email']);

        if (User::query()->where('email', $email)->exists()) {
            throw new ImportRowException('email', __('messages.import_email_exists', ['email' => $email]));
        }

        $groupIds = StudentGroup::query()->where('is_active', true)->where('code', $row['group_code'])->pluck('id');

        if ($groupIds->isEmpty()) {
            throw new ImportRowException('group_code', __('messages.import_group_not_found', ['code' => $row['group_code']]));
        }

        if ($groupIds->count() > 1) {
            throw new ImportRowException('group_code', __('messages.import_group_ambiguous', ['code' => $row['group_code']]));
        }

        if (! empty($row['student_number']) && StudentProfile::query()->where('student_number', $row['student_number'])->exists()) {
            throw new ImportRowException('student_number', __('messages.import_student_number_exists', ['number' => $row['student_number']]));
        }

        return [
            'name' => $row['name'],
            'email' => $email,
            'role' => UserRole::Student,
            'student_profile' => [
                'student_group_id' => $groupIds->first(),
                'student_number' => $row['student_number'] ?? null,
                'phone' => $row['phone'] ?? null,
            ],
        ];
    }

    public function persist(array $payload, User $actor): void
    {
        $this->provisionUser->execute($payload, $actor);
    }
}
