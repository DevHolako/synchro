<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamGrade;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

/**
 * After a module's weighting changes, recomputes the stored final grades of its draft and
 * submitted grade sheets, with one write per exam. Locked sheets keep the finals they were
 * deliberated with.
 */
class RecomputeOpenFinalGradesAction
{
    public function __construct(private CalculateFinalGradeAction $calculate) {}

    public function execute(Module $module): void
    {
        $exams = Exam::query()
            ->where('module_id', $module->id)
            ->whereHas('deliberation', fn ($sheets) => $sheets->whereIn('status', [GradeSheetStatus::Draft, GradeSheetStatus::Submitted]))
            ->orderBy('id')
            ->get(['id']);

        foreach ($exams as $exam) {
            DB::transaction(function () use ($exam, $module): void {
                // A teacher's save locks the exam too, so it never mixes the two weightings.
                $exam->lockRow();

                if (! $exam->deliberation()->lockForUpdate()->firstOrFail()->status->isOpen()) {
                    return;
                }

                $now = now();
                $lines = $exam->grades()->get()->map(fn (ExamGrade $grade): array => [
                    'exam_id' => $grade->exam_id,
                    'student_id' => $grade->student_id,
                    'final_grade' => $this->calculate->execute(
                        $grade->continuous_assessment_grade,
                        $grade->exam_grade,
                        $grade->is_absent,
                        $module->continuous_assessment_weight,
                        $grade->previous_final_grade,
                    ),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                ExamGrade::query()->upsert($lines->all(), ['exam_id', 'student_id'], ['final_grade', 'updated_at']);
            });
        }
    }
}
