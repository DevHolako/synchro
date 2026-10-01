<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\InvitationToken;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserProfileSeeder extends Seeder
{
    /**
     * Attach role profiles to seeded teachers and provision sample student accounts.
     */
    public function run(): void
    {
        User::teachers()
            ->whereDoesntHave('teacherProfile')
            ->with('taughtModules.program')
            ->get()
            ->each(fn (User $teacher) => $teacher->teacherProfile()->create([
                'department_id' => $teacher->taughtModules->first()?->program->department_id,
                'employee_number' => 'ENS-'.str_pad((string) $teacher->id, 5, '0', STR_PAD_LEFT),
            ]));

        $group = StudentGroup::query()->orderBy('id')->first();

        $student = User::firstOrCreate(
            ['email' => 'student1@synchro.isga.ma'],
            [
                'name' => 'Student Synchro',
                'password' => 'password',
                'role' => UserRole::Student,
                'status' => AccountStatus::Active,
                'activated_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $student->studentProfile()->firstOrCreate([], [
            'student_group_id' => $group?->id,
            'student_number' => 'ETU-000001',
        ]);

        $invited = User::firstOrCreate(
            ['email' => 'invited.student@synchro.isga.ma'],
            [
                'name' => 'Invited Student',
                'password' => Str::password(64),
                'role' => UserRole::Student,
                'status' => AccountStatus::Invited,
            ]
        );

        $invited->studentProfile()->firstOrCreate([], ['student_group_id' => $group?->id]);

        if (! $invited->invitationTokens()->pending()->exists()) {
            $invited->invitationTokens()->create([
                'token_hash' => InvitationToken::hashToken(Str::random(64)),
                'expires_at' => now()->addHours(InvitationToken::LIFETIME_HOURS),
            ]);
        }
    }
}
