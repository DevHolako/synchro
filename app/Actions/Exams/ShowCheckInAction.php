<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\User;
use App\Support\SchoolClock;

/**
 * What the door check-in screen shows for a scanned convocation: who the candidate is, where
 * they sit, whether the scanning invigilator is in the right room, and the check-in state.
 */
class ShowCheckInAction
{
    /**
     * @return array{
     *     candidate: array{id: int, name: string, student_number: string|null, group: string|null, room: string, building: string, seat: int},
     *     exam: array{id: int, module: string, start: string, end: string},
     *     checkIn: array{open: bool, opens_at: string, checked_in_at: string|null, checked_in_by: string|null, wrong_room: bool, can_check_in: bool, can_undo: bool},
     *     viewerRoomId: int|null
     * }
     */
    public function execute(ExamCandidate $candidate, User $viewer): array
    {
        $candidate->loadMissing([
            'exam.module:id,code,name',
            'student.studentProfile.studentGroup:id,name',
            'roomAssignment.room.building:id,name',
            'checker:id,name',
        ]);
        $exam = $candidate->exam;
        $profile = $candidate->student->studentProfile;
        $viewerRoomId = $exam->invigilatedAssignmentId($viewer);
        $wrongRoom = $viewerRoomId !== null && $viewerRoomId !== $candidate->exam_room_assignment_id;
        $open = $exam->isCheckInOpen();
        $present = $candidate->checked_in_at !== null;

        return [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->student->officialName(),
                'student_number' => $profile?->student_number,
                'group' => $profile?->studentGroup?->name,
                'room' => $candidate->roomAssignment->room->name,
                'building' => $candidate->roomAssignment->room->building->name,
                'seat' => $candidate->seat_number,
            ],
            'exam' => [
                'id' => $exam->id,
                'module' => $exam->module->label(),
                'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(SchoolClock::WALL_CLOCK_FORMAT),
            ],
            'checkIn' => [
                'open' => $open,
                'opens_at' => $exam->starts_at->copy()->subMinutes($exam::CHECK_IN_OPENS_MINUTES_BEFORE)->format(SchoolClock::WALL_CLOCK_FORMAT),
                'checked_in_at' => $candidate->checked_in_at?->copy()->setTimezone((string) config('app.schedule_timezone'))->format('H:i'),
                'checked_in_by' => $candidate->checker?->name,
                'wrong_room' => $wrongRoom,
                'can_check_in' => $open && ! $wrongRoom && ! $present,
                'can_undo' => $open && ! $wrongRoom && $present,
            ],
            // The scanning invigilator's own room page, to go back to.
            'viewerRoomId' => $viewerRoomId,
        ];
    }
}
