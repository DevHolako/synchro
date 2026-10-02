<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\BookingSlot;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Support\Facades\DB;

/**
 * The write path shared by scheduling and editing a session: guard, save, link groups,
 * and audit any overridden soft conflicts, all in one transaction.
 */
class SaveCourseSessionAction
{
    public function __construct(
        private GuardSessionConflictsAction $guardConflicts,
        private RecordConflictOverridesAction $recordOverrides,
    ) {}

    /**
     * @param array{
     *     module_id: int,
     *     teacher_id: int,
     *     room_id: int,
     *     student_group_ids: list<int>,
     *     starts_at: string,
     *     ends_at: string
     * } $data
     *
     * @throws HardConflictException
     * @throws SoftConflictException
     */
    public function execute(CourseSession $session, array $data, ?SoftConflictOverride $override = null): CourseSession
    {
        return DB::transaction(function () use ($session, $data, $override): CourseSession {
            $existed = $session->exists;
            $slot = BookingSlot::fromPayload($data, $existed ? $session->id : null);
            $result = $this->guardConflicts->execute($slot, $override);

            $session->fill([
                'module_id' => $data['module_id'],
                'teacher_id' => $data['teacher_id'],
                'room_id' => $data['room_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ])->save();

            $changes = $session->studentGroups()->sync($data['student_group_ids']);

            // A change to the groups alone is still a change to the session (iCal SEQUENCE, LAST-MODIFIED).
            if ($existed && ! $session->wasChanged() && array_filter($changes) !== []) {
                $session->touch();
            }

            if ($override !== null) {
                $this->recordOverrides->execute($session, $result, $override);
            }

            return $session;
        });
    }
}
