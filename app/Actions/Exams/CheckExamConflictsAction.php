<?php

namespace App\Actions\Exams;

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
        return $this->detector->checkConflicts(SessionSlot::forExam($data, $ignoreExamId));
    }
}
