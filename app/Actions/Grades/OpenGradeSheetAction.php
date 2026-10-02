<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use App\Models\ExamGrade;
use Illuminate\Support\Facades\DB;

/**
 * An exam's grade sheet, created the first time it is opened. While it is a draft, every
 * candidate without a line gets one, so students added to the exam join on the next opening.
 */
class OpenGradeSheetAction
{
    public function execute(Exam $exam): ExamDeliberation
    {
        return DB::transaction(function () use ($exam): ExamDeliberation {
            $exam->lockRow();

            $sheet = ExamDeliberation::query()->firstOrCreate(['exam_id' => $exam->id]);

            if ($sheet->status === GradeSheetStatus::Draft) {
                $this->addMissingCandidates($exam);
            }

            $exam->setRelation('deliberation', $sheet);

            return $sheet;
        });
    }

    /**
     * New lines start absent when the candidate never checked in, but only if the door check-in
     * was used for this exam: with paper sheets alone, nobody is checked in.
     */
    private function addMissingCandidates(Exam $exam): void
    {
        $missing = $exam->candidates()
            ->whereNotIn('student_id', $exam->grades()->select('student_id'))
            ->get(['student_id', 'checked_in_at']);

        if ($missing->isEmpty()) {
            return;
        }

        $checkInUsed = $exam->candidates()->whereNotNull('checked_in_at')->exists();
        $now = now();

        ExamGrade::query()->insert($missing->map(fn ($candidate): array => [
            'exam_id' => $exam->id,
            'student_id' => $candidate->student_id,
            'is_absent' => $checkInUsed && $candidate->checked_in_at === null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }
}
