<?php

namespace App\Policies;

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
     * Determine whether the user can download their own convocation: they may see exams, sit
     * this one, and it has been published.
     */
    public function downloadConvocation(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ViewExams)
            && $exam->state->isVisibleToCandidates()
            && $this->sits($user, $exam);
    }

    /**
     * Determine whether the user can download the door lists and attendance sheets: exam
     * managers, and the exam's invigilators once it is published.
     */
    public function downloadRoster(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams)
            || ($user->hasPermission(Permission::ViewExams) && $exam->state->isVisibleToCandidates() && $this->invigilates($user, $exam));
    }

    /**
     * Determine whether the user can check candidates in at the door: exam managers, and the
     * exam's invigilators who record attendance (which room they may check in is the check-in
     * action's rule).
     */
    public function checkIn(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams)
            || ($user->hasPermission(Permission::RecordAttendance) && $this->invigilates($user, $exam));
    }

    /**
     * Determine whether the user can open a room's check-in list: exam managers, and the
     * invigilators of that room who record attendance.
     */
    public function checkInRoom(User $user, Exam $exam, ExamRoomAssignment $room): bool
    {
        return $room->exam_id === $exam->id
            && ($user->hasPermission(Permission::ManageExams)
                || ($user->hasPermission(Permission::RecordAttendance) && $exam->invigilatedAssignmentId($user) === $room->id));
    }

    /**
     * Determine whether the user can read an exam's grade sheet: its module teacher who enters
     * grades, exam managers and those who lock deliberations, once the exam can be graded.
     */
    public function viewGrades(User $user, Exam $exam): bool
    {
        return $exam->isGradable()
            && ($this->gradesModule($user, $exam)
                || $user->hasPermission(Permission::ManageExams)
                || $user->hasPermission(Permission::LockGrades));
    }

    /**
     * Determine whether the user can enter grades on an exam's sheet: only the module's teacher,
     * holding the permission, once the exam can be graded (whether the sheet is still a draft is
     * the grade actions' rule).
     */
    public function enterGrades(User $user, Exam $exam): bool
    {
        return $exam->isGradable() && $this->gradesModule($user, $exam);
    }

    /**
     * Determine whether the user can remove an exam.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::ManageExams);
    }

    private function gradesModule(User $user, Exam $exam): bool
    {
        return $user->hasPermission(Permission::EnterGrades) && $exam->module->teacher_id === $user->id;
    }

    /**
     * Uses the invigilators already loaded, else one query. A loaded relation holds either all of
     * the exam's invigilators or (on the exams list) only the viewer's, and the list only ever
     * asks about its viewer, so `contains()` gives the same answer as the query.
     */
    private function invigilates(User $user, Exam $exam): bool
    {
        return $exam->relationLoaded('invigilators')
            ? $exam->invigilators->contains('teacher_id', $user->id)
            : $exam->invigilators()->where('teacher_id', $user->id)->exists();
    }

    /**
     * Uses the candidates already loaded, else one query (same reasoning as invigilates()).
     */
    private function sits(User $user, Exam $exam): bool
    {
        return $exam->relationLoaded('candidates')
            ? $exam->candidates->contains('student_id', $user->id)
            : $exam->candidates()->where('student_id', $user->id)->exists();
    }
}
