<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\ExamRoomAssignment;
use App\Support\SchoolClock;

/**
 * One exam room's check-in list: every candidate in seat order, present or not yet arrived.
 */
class ShowRoomCheckInAction
{
    /**
     * @return array{
     *     exam: array{id: int, module: string, start: string, end: string},
     *     room: array{id: int, name: string, building: string, first_surname: string|null, last_surname: string|null},
     *     open: bool,
     *     candidates: list<array{id: int, seat: int, name: string, student_number: string|null, checked_in_at: string|null}>
     * }
     */
    public function execute(ExamRoomAssignment $room): array
    {
        $room->loadMissing(['exam.module:id,code,name', 'room.building:id,name']);
        $candidates = $room->candidates()
            ->with(['student:id,name', 'student.studentProfile:id,user_id,last_name,first_name,student_number'])
            ->orderBy('seat_number')
            ->get();
        $exam = $room->exam;
        $timezone = (string) config('app.schedule_timezone');

        return [
            'exam' => [
                'id' => $exam->id,
                'module' => $exam->module->label(),
                'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(SchoolClock::WALL_CLOCK_FORMAT),
            ],
            'room' => [
                'id' => $room->id,
                'name' => $room->room->name,
                'building' => $room->room->building->name,
                'first_surname' => $room->first_surname,
                'last_surname' => $room->last_surname,
            ],
            'open' => $exam->isCheckInOpen(),
            'candidates' => array_values($candidates->map(fn (ExamCandidate $candidate): array => [
                'id' => $candidate->id,
                'seat' => $candidate->seat_number,
                'name' => $candidate->student->officialName(),
                'student_number' => $candidate->student->studentProfile?->student_number,
                'checked_in_at' => $candidate->checked_in_at?->copy()->setTimezone($timezone)->format('H:i'),
            ])->all()),
        ];
    }
}
