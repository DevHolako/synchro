<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\BatchConflictException;
use App\Exceptions\ConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Support\Facades\DB;

/**
 * Schedules several sessions of one module at once, all or nothing (Part 03 / Ticket 02).
 *
 * Every slot goes through the single-session write path inside one transaction, so each
 * is locked and re-checked, and slots of the same batch see each other: two overlapping
 * slots collide like any double booking. The first slot with a conflict that is not
 * overridden rolls the whole batch back. One override covers every soft conflict of the
 * batch, audited per session.
 */
class BatchCreateCourseSessionsAction
{
    public function __construct(private SaveCourseSessionAction $save) {}

    /**
     * @param array{
     *     module_id: int,
     *     teacher_id: int,
     *     room_id: int,
     *     student_group_ids: list<int>,
     *     slots: list<array{starts_at: string, ends_at: string}>
     * } $data
     * @return list<CourseSession>
     *
     * @throws BatchConflictException
     */
    public function execute(array $data, ?SoftConflictOverride $override = null): array
    {
        return DB::transaction(function () use ($data, $override): array {
            $sessions = [];

            foreach ($data['slots'] as $index => $slot) {
                try {
                    $sessions[] = $this->save->execute(new CourseSession, [
                        'module_id' => $data['module_id'],
                        'teacher_id' => $data['teacher_id'],
                        'room_id' => $data['room_id'],
                        'student_group_ids' => $data['student_group_ids'],
                        'starts_at' => $slot['starts_at'],
                        'ends_at' => $slot['ends_at'],
                    ], $override);
                } catch (ConflictException $conflict) {
                    throw new BatchConflictException($index, $slot, $conflict);
                }
            }

            return $sessions;
        });
    }
}
