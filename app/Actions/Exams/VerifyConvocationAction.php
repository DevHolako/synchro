<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Finds the candidate behind a scanned convocation. The link's signature is checked before
 * (the `signed` middleware), so a tampered link never reaches this point.
 */
class VerifyConvocationAction
{
    /**
     * @throws ModelNotFoundException When no convocation carries this identity.
     */
    public function execute(string $uuid): ExamCandidate
    {
        return ExamCandidate::query()
            ->where('convocation_uuid', $uuid)
            ->with([
                'exam.module:id,code,name',
                'student.studentProfile.studentGroup:id,name',
                'roomAssignment.room.building:id,name',
            ])
            ->firstOrFail();
    }
}
