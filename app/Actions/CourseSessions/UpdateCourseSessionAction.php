<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Support\Facades\DB;

class UpdateCourseSessionAction
{
    public function __construct(
        private GuardSessionConflictsAction $guardConflicts,
        private RecordConflictOverridesAction $recordOverrides,
    ) {}

    /**
     * Move or re-staff a session, refusing any hard conflict with other sessions and any soft
     * conflict not overridden. Every edit is checked afresh, so a standing soft conflict needs a new override.
     *
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
            $result = $this->guardConflicts->execute(CreateCourseSessionAction::slot($data, $session->id), $override);

            $session->update([
                'module_id' => $data['module_id'],
                'teacher_id' => $data['teacher_id'],
                'room_id' => $data['room_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ]);

            $session->studentGroups()->sync($data['student_group_ids']);

            if ($override !== null) {
                $this->recordOverrides->execute($session, $result, $override);
            }

            return $session;
        });
    }
}
