<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\SoftConflictOverride;

class CreateCourseSessionAction
{
    public function __construct(private SaveCourseSessionAction $save) {}

    /**
     * Schedule a session, refusing any hard conflict and any soft conflict not overridden.
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
    public function execute(array $data, ?SoftConflictOverride $override = null): CourseSession
    {
        return $this->save->execute(new CourseSession, $data, $override);
    }
}
