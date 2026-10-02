<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ExamPeriod;
use App\Models\User;

class ExamPeriodPolicy
{
    /**
     * Determine whether the user can create exam periods.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }

    /**
     * Determine whether the user can edit a period, or publish or archive its exams.
     */
    public function update(User $user, ExamPeriod $period): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }

    /**
     * Determine whether the user can remove a period.
     */
    public function delete(User $user, ExamPeriod $period): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }
}
