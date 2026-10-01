<?php

namespace App\Actions\Unavailabilities;

use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Rejects a candidate unavailability that overlaps one of the teacher's active requests of the same type.
 *
 * Must run inside the caller's transaction: it locks the teacher's row so two concurrent
 * submissions for the same teacher cannot both pass the check.
 */
class GuardUnavailabilityOverlapAction
{
    /**
     * @throws ValidationException
     */
    public function execute(TeacherUnavailability $candidate): void
    {
        User::query()->whereKey($candidate->teacher_id)->lockForUpdate()->first();

        if (TeacherUnavailability::query()->overlapping($candidate)->exists()) {
            throw ValidationException::withMessages([
                'start_date' => __('messages.unavailability_overlaps'),
            ]);
        }
    }
}
