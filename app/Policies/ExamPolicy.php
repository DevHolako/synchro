<?php

namespace App\Policies;

use App\Enums\ExamState;
use App\Enums\Permission;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
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
     * Determine whether the user can download their own convocation: they sit the exam and it
     * has been published.
     */
    public function downloadConvocation(User $user, Exam $exam): bool
    {
        return in_array($exam->state, ExamState::visibleToCandidates(), true)
            && $exam->candidates()->where('student_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can download the door lists and attendance sheets: exam
     * managers, and the exam's invigilators once it is published.
     */
    public function downloadRoster(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams)
            || (in_array($exam->state, ExamState::visibleToCandidates(), true) && $this->invigilates($user, $exam));
    }

    /**
     * Determine whether the user can check candidates in at the door: exam managers and the
     * exam's invigilators (which room they may check in is the check-in action's rule).
     */
    public function checkIn(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams) || $this->invigilates($user, $exam);
    }

    /**
     * Determine whether the user can open a room's check-in list: exam managers, and the
     * invigilators of that room.
     */
    public function checkInRoom(User $user, Exam $exam, ExamRoomAssignment $room): bool
    {
        return $room->exam_id === $exam->id
            && ($user->hasPermission(Permission::ManageExams) || $exam->invigilatedRoomId($user) === $room->id);
    }

    /**
     * Determine whether the user can remove an exam.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }

    private function invigilates(User $user, Exam $exam): bool
    {
        return $exam->invigilators()->where('teacher_id', $user->id)->exists();
    }
}
