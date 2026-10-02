<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\User;

/**
 * What the door check-in screen shows for a scanned convocation: who the candidate is, where
 * they sit, whether the scanning invigilator is in the right room, and the check-in state.
 */
class ShowCheckInAction
{
    private const string WALL_CLOCK_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @return array<string, mixed>
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
        $viewerRoomId = $exam->invigilatedRoomId($viewer);
        $wrongRoom = $viewerRoomId !== null && $viewerRoomId !== $candidate->exam_room_assignment_id;
        $open = $exam->isCheckInOpen();
        $present = $candidate->checked_in_at !== null;

        return [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->student->name,
                'student_number' => $profile?->student_number,
                'group' => $profile?->studentGroup?->name,
                'room' => $candidate->roomAssignment->room->name,
                'building' => $candidate->roomAssignment->room->building->name,
                'seat' => $candidate->seat_number,
            ],
            'exam' => [
                'id' => $exam->id,
                'module' => "{$exam->module->code} · {$exam->module->name}",
                'start' => $exam->starts_at->format(self::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(self::WALL_CLOCK_FORMAT),
            ],
            'checkIn' => [
                'open' => $open,
                'opens_at' => $exam->starts_at->copy()->subMinutes($exam::CHECK_IN_OPENS_MINUTES_BEFORE)->format(self::WALL_CLOCK_FORMAT),
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
