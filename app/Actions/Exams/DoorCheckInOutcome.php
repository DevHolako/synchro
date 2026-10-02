<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\SupersededConvocation;

final readonly class DoorCheckInOutcome
{
    public function __construct(
        public string $status,
        public string $message,
        public int $statusCode = 200,
        public ?ExamCandidate $candidate = null,
        public ?SupersededConvocation $superseded = null,
        public ?string $assignedRoom = null,
        public ?string $currentRoom = null,
        public ?int $currentSeatNumber = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isSuperseded(): bool
    {
        return $this->status === 'superseded';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        if ($this->superseded !== null) {
            return [
                'status' => 'superseded',
                'message' => $this->message,
                'superseded_at' => $this->superseded->created_at->toIso8601String(),
                'candidate' => [
                    'id' => $this->superseded->id,
                    'name' => $this->superseded->student->officialName(),
                    'matricule' => $this->superseded->student->studentProfile?->matricule,
                    'current_room' => $this->currentRoom,
                    'current_seat_number' => $this->currentSeatNumber,
                ],
            ];
        }

        if ($this->candidate === null) {
            return [
                'status' => $this->status,
                'message' => $this->message,
            ];
        }

        $candidatePayload = [
            'id' => $this->candidate->id,
            'name' => $this->candidate->student->officialName(),
            'matricule' => $this->candidate->student->studentProfile?->matricule,
            'room' => $this->candidate->roomAssignment->room->name,
            'seat_number' => $this->candidate->seat_number,
        ];

        if ($this->status === 'success') {
            $candidatePayload['checked_in_at'] = $this->candidate->checked_in_at?->toIso8601String();

            return [
                'status' => 'success',
                'message' => $this->message,
                'candidate' => $candidatePayload,
            ];
        }

        if ($this->status === 'already_checked_in') {
            return [
                'status' => 'already_checked_in',
                'message' => $this->message,
                'checked_in_at' => $this->candidate->checked_in_at?->toIso8601String(),
                'candidate' => $candidatePayload,
            ];
        }

        if ($this->status === 'wrong_room') {
            return [
                'status' => 'wrong_room',
                'message' => $this->message,
                'assigned_room' => $this->assignedRoom ?? $this->candidate->roomAssignment->room->name,
                'candidate' => $candidatePayload,
            ];
        }

        return [
            'status' => 'closed',
            'message' => $this->message,
            'candidate' => $candidatePayload,
        ];
    }
}
