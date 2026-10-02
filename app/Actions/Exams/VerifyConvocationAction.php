<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\SupersededConvocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Finds what a scanned convocation stands for: the candidate it seats, or the convocation an
 * emergency reschedule superseded. The link's signature is checked before (the `signed`
 * middleware), so a tampered link never reaches this point.
 */
class VerifyConvocationAction
{
    /**
     * @throws ModelNotFoundException When no convocation, current or superseded, carries this identity.
     */
    public function execute(string $uuid): ExamCandidate|SupersededConvocation
    {
        return ExamCandidate::query()->where('convocation_uuid', $uuid)->first()
            ?? SupersededConvocation::query()->where('convocation_uuid', $uuid)->firstOrFail();
    }
}
