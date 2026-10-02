<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\SupersededConvocation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ScanDoorCheckInAction
{
    public function __construct(
        private readonly VerifyConvocationAction $verifyConvocation,
        private readonly CheckInCandidateAction $checkInAction,
    ) {}

    public function execute(string $rawToken, User $scanner): DoorCheckInOutcome
    {
        $uuid = $this->extractUuid($rawToken);

        $convocation = $this->verifyConvocation->execute($uuid);

        if ($convocation instanceof SupersededConvocation) {
            $currentCandidate = ExamCandidate::query()
                ->where('exam_id', $convocation->exam_id)
                ->where('student_id', $convocation->student_id)
                ->with('roomAssignment.room')
                ->first();

            return new DoorCheckInOutcome(
                status: 'superseded',
                message: __('messages.exam_superseded_notice'),
                statusCode: 409,
                superseded: $convocation,
                currentRoom: $currentCandidate?->roomAssignment?->room?->name,
                currentSeatNumber: $currentCandidate?->seat_number,
            );
        }

        /** @var ExamCandidate $convocation */
        Gate::authorize('checkIn', $convocation->exam);

        try {
            $checkedIn = $this->checkInAction->execute($convocation, $scanner);

            return new DoorCheckInOutcome(
                status: 'success',
                message: __('messages.exam_checked_in', ['name' => $checkedIn->student->officialName()]),
                statusCode: 200,
                candidate: $checkedIn,
            );
        } catch (ValidationException $e) {
            $errorMessage = $e->errors()['check_in'][0] ?? $e->getMessage();

            if ($convocation->checked_in_at !== null) {
                return new DoorCheckInOutcome(
                    status: 'already_checked_in',
                    message: $errorMessage,
                    statusCode: 422,
                    candidate: $convocation,
                );
            }

            $userRoomId = $convocation->exam->invigilatedAssignmentId($scanner);
            if ($userRoomId !== null && $userRoomId !== $convocation->exam_room_assignment_id) {
                return new DoorCheckInOutcome(
                    status: 'wrong_room',
                    message: $errorMessage,
                    statusCode: 422,
                    candidate: $convocation,
                    assignedRoom: $convocation->roomAssignment->room->name,
                );
            }

            return new DoorCheckInOutcome(
                status: 'closed',
                message: $errorMessage,
                statusCode: 422,
                candidate: $convocation,
            );
        }
    }

    private function extractUuid(string $raw): string
    {
        if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $raw, $matches)) {
            return $matches[0];
        }

        return $raw;
    }
}
