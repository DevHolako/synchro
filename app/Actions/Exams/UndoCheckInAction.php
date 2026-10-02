<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Cancels a mistaken check-in while the exam is still open for check-in, under the same room
 * rule as checking in.
 */
class UndoCheckInAction
{
    public function __construct(private CheckInCandidateAction $checkIn) {}

    /**
     * @throws ValidationException
     */
    public function execute(ExamCandidate $candidate, User $user): ExamCandidate
    {
        $this->checkIn->ensureAllowed($candidate, $user);

        $candidate->update(['checked_in_at' => null, 'checked_in_by' => null]);

        return $candidate;
    }
}
