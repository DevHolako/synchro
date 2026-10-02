<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamGrade;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

/**
 * After a module's weighting changes, recomputes the stored final grades of its draft and
 * submitted grade sheets. Locked sheets keep the finals they were deliberated with.
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

                $exam->grades()->each(function (ExamGrade $grade) use ($module): void {
                    $grade->update(['final_grade' => $this->calculate->execute(
                        $grade->continuous_assessment_grade,
                        $grade->exam_grade,
                        $grade->is_absent,
                        $module->continuous_assessment_weight,
                    )]);
                });
            });
        }
    }
}
