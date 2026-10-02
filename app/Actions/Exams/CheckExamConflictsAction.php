<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\ConflictResult;
use App\Services\Scheduling\SessionSlot;

/**
 * Previews the conflicts an exam would cause once scheduled, without locking or saving anything.
 */
class CheckExamConflictsAction
{
    public function __construct(private ConflictDetectorService $detector) {}

    /**
     * @param  array{student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     */
    public function execute(array $data, ?int $ignoreExamId = null): ConflictResult
    {
        $slot = SessionSlot::forExam($data, $ignoreExamId);
        $exam = $ignoreExamId === null ? null : Exam::find($ignoreExamId);

        // An existing exam keeps its rooms and invigilators at the new time.
        if ($exam !== null) {
            $booked = $exam->bookingSlot();
            $slot = new SessionSlot(
                type: $slot->type,
                teacherIds: $booked->teacherIds,
                roomIds: $booked->roomIds,
                groupIds: $slot->groupIds,
                startsAt: $slot->startsAt,
                endsAt: $slot->endsAt,
                ignoreId: $exam->id,
            );
        }

        return $this->detector->checkConflicts($slot);
    }
}
