<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Jobs\GenerateDeliberationPvJob;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The coordinator locks and signs a submitted deliberation (ADR 0006): its grades become
 * immutable and visible to students, the weighting and figures it was decided with are kept,
 * and its official PV is generated and archived in the background.
 */
class LockDeliberationAction
{
    public function __construct(
        private LockSubmittedGradeSheetAction $lockSubmitted,
        private ListGradeLinesAction $listLines,
        private CalculateDeliberationStatsAction $stats,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(Exam $exam, User $coordinator): ExamDeliberation
    {
        return DB::transaction(function () use ($exam, $coordinator): ExamDeliberation {
            $sheet = $this->lockSubmitted->execute($exam);
            $stats = $this->stats->execute($this->listLines->execute($exam));

            $sheet->update([
                'status' => GradeSheetStatus::Locked,
                'locked_at' => now(),
                'locked_by' => $coordinator->id,
                'continuous_assessment_weight' => (int) $exam->module()->value('continuous_assessment_weight'),
                'class_average' => $stats['average'],
                'pass_rate' => $stats['pass_rate'],
            ]);

            // After commit: a rolled-back lock archives no PV (ADR 0012).
            DB::afterCommit(fn () => GenerateDeliberationPvJob::dispatch($sheet));

            return $sheet;
        });
    }
}
