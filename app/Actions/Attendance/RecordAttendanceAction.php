<?php

namespace App\Actions\Attendance;

use App\Models\CourseSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a session's register in one go: each student's mark is created or replaced.
 * Students left unmarked are not stored.
 */
class RecordAttendanceAction
{
    /**
     * @param  list<array{student_id: int, status: string, remarks: string|null}>  $marks
     */
    public function execute(CourseSession $session, array $marks, User $recorder): void
    {
        if ($marks === []) {
            return;
        }

        $now = now();

        DB::transaction(fn () => SessionAttendance::query()->upsert(
            array_map(fn (array $mark): array => [
                'course_session_id' => $session->id,
                'student_id' => $mark['student_id'],
                'status' => $mark['status'],
                'remarks' => $mark['remarks'],
                'recorded_by' => $recorder->id,
                'created_at' => $now,
                'updated_at' => $now,
            ], $marks),
            ['course_session_id', 'student_id'],
            ['status', 'remarks', 'recorded_by', 'updated_at'],
        ));
    }
}
