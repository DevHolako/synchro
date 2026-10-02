<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamGrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sends a complete draft grade sheet to coordination. From then on the teacher can no longer
 * change it; only a coordinator can send it back (ticket 03).
 */
class SubmitGradeSheetAction
{
    /** How many incomplete students the refusal names. */
    private const int NAMED_INCOMPLETE = 5;

    public function __construct(private LockDraftGradeSheetAction $lockDraft) {}

    /**
     * @throws ValidationException
     */
    public function execute(Exam $exam, User $teacher): void
    {
        DB::transaction(function () use ($exam, $teacher): void {
            $sheet = $this->lockDraft->execute($exam);
            $weight = (int) $exam->module()->value('continuous_assessment_weight');

            $grades = $exam->grades()
                ->with(['student:id,name', 'student.studentProfile:id,user_id,last_name,first_name'])
                ->get();

            if ($grades->isEmpty()) {
                throw ValidationException::withMessages(['grades' => __('messages.grade_sheet_empty')]);
            }

            // A retake's CC is carried over, not entered: only the retake itself is checked.
            $incomplete = $grades->reject(fn (ExamGrade $grade): bool => $this->isComplete($grade, $exam->isRetake() ? 0 : $weight));

            if ($incomplete->isNotEmpty()) {
                throw ValidationException::withMessages(['grades' => __('messages.grade_sheet_incomplete', [
                    'count' => $incomplete->count(),
                    'names' => $incomplete->take(self::NAMED_INCOMPLETE)
                        ->map(fn (ExamGrade $grade): string => $grade->student->officialName())
                        ->implode(', '),
                ])]);
            }

            $sheet->update([
                'status' => GradeSheetStatus::Submitted,
                'submitted_at' => now(),
                'submitted_by' => $teacher->id,
                'returned_at' => null,
                'return_reason' => null,
            ]);
        });
    }

    /**
     * A line is complete with its CC grade (when CC counts) and either an exam grade or an
     * absence with its remark.
     */
    private function isComplete(ExamGrade $grade, int $weight): bool
    {
        if ($weight > 0 && $grade->continuous_assessment_grade === null) {
            return false;
        }

        return $grade->is_absent ? filled($grade->remarks) : $grade->exam_grade !== null;
    }
}
