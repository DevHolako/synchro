<?php

namespace App\Actions\Users;

use App\Actions\Invitations\IssueInvitationAction;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionUserAction
{
    public function __construct(private readonly IssueInvitationAction $issueInvitation) {}

    /**
     * Create an invited account with its role profile and dispatch an Invitation Token.
     *
     * A student's display name is made from their official given name and surname.
     *
     * @param array{
     *     name?: string|null,
     *     email: string,
     *     role: string|UserRole,
     *     teacher_profile?: array{department_id?: int|null, employee_number?: string|null, phone?: string|null}|null,
     *     student_profile?: array{last_name?: string, first_name?: string, student_group_id?: int|null, student_number?: string|null, phone?: string|null}|null
     * } $data
     */
    public function execute(array $data, ?User $invitedBy = null): User
    {
        $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);

        $user = DB::transaction(function () use ($data, $role): User {
            $profile = $data['student_profile'] ?? null;
            $name = isset($profile['last_name'], $profile['first_name'])
                ? StudentProfile::displayName($profile['first_name'], $profile['last_name'])
                : trim((string) ($data['name'] ?? ''));

            $user = User::create([
                'name' => $name,
                'email' => Str::lower(trim($data['email'])),
                // Unusable random secret until the invitee sets their own password.
                'password' => Str::password(64),
                'role' => $role,
                'status' => AccountStatus::Invited,
            ]);

            $profileRelation = $role->profileRelation();

            if ($profileRelation !== null) {
                $user->{$profileRelation}()->create($data[Str::snake($profileRelation)] ?? []);
            }

            return $user;
        });

        // Deferred so a surrounding transaction (e.g. a bulk import) that rolls back sends no invitations.
        DB::afterCommit(fn () => $this->issueInvitation->execute($user, $invitedBy));

        return $user;
    }
}
