<?php

namespace App\Actions\Attendance;

use App\Models\CourseSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a session's register in one go: each student's mark is created, replaced, or removed
 * when sent without a status. Students never marked are not stored.
 */
class RecordAttendanceAction
{
    /**
     * @param  list<array{student_id: int, status: string|null, remarks: string|null}>  $marks
     */
    public function execute(CourseSession $session, array $marks, User $recorder): void
    {
        $cleared = array_column(array_filter($marks, fn (array $mark): bool => $mark['status'] === null), 'student_id');
        $kept = array_values(array_filter($marks, fn (array $mark): bool => $mark['status'] !== null));

        DB::transaction(function () use ($session, $cleared, $kept, $recorder): void {
            if ($cleared !== []) {
                $session->attendances()->whereIn('student_id', $cleared)->delete();
            }

            if ($kept !== []) {
                $this->upsert($session, $kept, $recorder);
            }
        });
    }

    /**
     * @param  list<array{student_id: int, status: string|null, remarks: string|null}>  $marks
     */
    private function upsert(CourseSession $session, array $marks, User $recorder): void
    {
        $now = now();

        SessionAttendance::query()->upsert(
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
        );
    }
}
