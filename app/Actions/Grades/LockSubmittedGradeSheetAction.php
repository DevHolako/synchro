<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use Illuminate\Validation\ValidationException;

/**
 * Locks an exam, then its grade sheet, for a coordinator's decision (run inside the decision's
 * transaction), and refuses unless the sheet is waiting for one: submitted.
 */
class LockSubmittedGradeSheetAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Exam $exam): ExamDeliberation
    {
        $exam->lockRow();

        $sheet = ExamDeliberation::query()->where('exam_id', $exam->id)->lockForUpdate()->first();

        if ($sheet === null || $sheet->status !== GradeSheetStatus::Submitted) {
            throw ValidationException::withMessages(['deliberation' => __('messages.deliberation_not_submitted')]);
        }

        return $sheet;
    }
}
