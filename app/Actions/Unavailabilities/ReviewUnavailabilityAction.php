<?php

namespace App\Actions\Unavailabilities;

use App\Enums\UnavailabilityStatus;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReviewUnavailabilityAction
{
    /**
     * Approve or reject a pending unavailability. Decisions are final.
     *
     * The status change is a conditional update on `pending`, so when two reviewers decide
     * at the same time only the first one wins.
     *
     * @throws ValidationException
     */
    public function execute(
        TeacherUnavailability $unavailability,
        User $reviewer,
        UnavailabilityStatus $decision,
        ?string $note = null,
    ): TeacherUnavailability {
        if ($decision === UnavailabilityStatus::Pending) {
            throw new InvalidArgumentException('A review must approve or reject.');
        }

        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        if ($decision === UnavailabilityStatus::Rejected && $note === null) {
            throw ValidationException::withMessages([
                'review_note' => __('messages.unavailability_rejection_note_required'),
            ]);
        }

        $claimed = TeacherUnavailability::query()
            ->whereKey($unavailability->id)
            ->where('status', UnavailabilityStatus::Pending)
            ->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

        if ($claimed === 0) {
            throw ValidationException::withMessages([
                'decision' => __('messages.unavailability_already_reviewed'),
            ]);
        }

        return $unavailability->refresh();
    }
}
