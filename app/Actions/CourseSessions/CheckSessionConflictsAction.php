<?php

namespace App\Actions\CourseSessions;

use App\Services\Scheduling\BookingSlot;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\ConflictResult;

/**
 * Previews the conflicts a session would cause, without locking or saving anything.
 */
class CheckSessionConflictsAction
{
    public function __construct(private ConflictDetectorService $detector) {}

    /**
     * @param  array{teacher_id: int, room_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     */
    public function execute(array $data, ?int $ignoreSessionId = null): ConflictResult
    {
        return $this->detector->checkConflicts(BookingSlot::fromPayload($data, $ignoreSessionId));
    }
}
