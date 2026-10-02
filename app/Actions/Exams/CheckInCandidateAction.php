<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Marks a candidate present at the exam door (ADR 0008), recording who did it and when.
 *
 * Only while check-in is open; an invigilator only checks in the candidates of their own room
 * (exam managers have no room and check in anyone); a second check-in is refused.
 */
class CheckInCandidateAction
{
    /**
     * @throws ValidationException
     */
    public function execute(ExamCandidate $candidate, User $checker): ExamCandidate
    {
        $this->ensureAllowed($candidate, $checker);

        // Conditional on "not yet present", so two invigilators scanning at once record it once.
        $marked = ExamCandidate::query()
            ->whereKey($candidate->id)
            ->whereNull('checked_in_at')
            ->update(['checked_in_at' => now(), 'checked_in_by' => $checker->id]);

        if ($marked === 0) {
            throw ValidationException::withMessages(['check_in' => __('messages.exam_already_checked_in')]);
        }

        return $candidate->refresh();
    }

    /**
     * Check-in is open, and the user is not an invigilator of another room.
     *
     * @throws ValidationException
     */
    public function ensureAllowed(ExamCandidate $candidate, User $user): void
    {
        $exam = $candidate->exam;

        if (! $exam->isCheckInOpen()) {
            throw ValidationException::withMessages(['check_in' => __('messages.exam_check_in_closed')]);
        }

        $roomId = $exam->invigilatedRoomId($user);

        if ($roomId !== null && $roomId !== $candidate->exam_room_assignment_id) {
            throw ValidationException::withMessages(['check_in' => __('messages.exam_check_in_wrong_room', [
                'room' => $candidate->roomAssignment->room->name,
            ])]);
        }
    }
}
