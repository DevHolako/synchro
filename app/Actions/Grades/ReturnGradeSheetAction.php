<?php

namespace App\Actions\Grades;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The coordinator sends a submitted grade sheet back to its teacher as a draft, saying why;
 * the reason stays on the sheet until it is submitted again.
 */
class ReturnGradeSheetAction
{
    public function __construct(private LockSubmittedGradeSheetAction $lockSubmitted) {}

    /**
     * @throws ValidationException
     */
    public function execute(Exam $exam, string $reason): void
    {
        DB::transaction(function () use ($exam, $reason): void {
            $this->lockSubmitted->execute($exam)->update([
                'status' => GradeSheetStatus::Draft,
                'submitted_at' => null,
                'submitted_by' => null,
                'returned_at' => now(),
                'return_reason' => $reason,
            ]);
        });
    }
}
