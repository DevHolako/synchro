<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use Illuminate\Validation\ValidationException;

/**
 * Locks an exam, then its grade sheet, for a teacher's write (run inside the write's
 * transaction), and refuses once the sheet has left the draft status: a save racing a
 * submission or a lock waits for it, then is turned away.
 */
class LockDraftGradeSheetAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Exam $exam): ExamDeliberation
    {
        $exam->lockRow();

        $sheet = ExamDeliberation::query()->where('exam_id', $exam->id)->lockForUpdate()->first();

        if ($sheet?->status !== GradeSheetStatus::Draft) {
            throw ValidationException::withMessages(['grades' => __('messages.grade_sheet_not_draft')]);
        }

        return $sheet;
    }
}
