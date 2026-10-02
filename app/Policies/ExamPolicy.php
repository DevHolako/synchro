<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    /**
     * Determine whether the user can open the exams page (which exams they see is scoped by `Exam::visibleTo`).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewExams)
            || $user->hasPermission(Permission::ManageExams);
    }

    /**
     * Determine whether the user can draft exams or check them for conflicts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }

    /**
     * Determine whether the user can edit an exam or move it along its lifecycle.
     */
    public function update(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }

    /**
     * Determine whether the user can remove an exam.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }
}
