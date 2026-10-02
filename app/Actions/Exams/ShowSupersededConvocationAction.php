<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\SupersededConvocation;
use App\Support\SchoolClock;

/**
 * What scanning a superseded convocation shows: a warning, and where the student now sits.
 */
class ShowSupersededConvocationAction
{
    /**
     * @return array{
     *     student: string,
     *     exam: array{module: string, start: string, end: string, revision: int},
     *     current: array{room: string, building: string, seat: int}|null
     * }
     */
    public function execute(SupersededConvocation $superseded): array
    {
        $exam = $superseded->exam()->with('module:id,code,name')->firstOrFail();
        $current = ExamCandidate::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $superseded->student_id)
            ->with('roomAssignment.room.building:id,name')
            ->first();

        return [
            'student' => $superseded->student->officialName(),
            'exam' => [
                'module' => $exam->module->label(),
                'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(SchoolClock::WALL_CLOCK_FORMAT),
                'revision' => $exam->revision,
            ],
            // Null when the student no longer sits the exam.
            'current' => $current === null ? null : [
                'room' => $current->roomAssignment->room->name,
                'building' => $current->roomAssignment->room->building->name,
                'seat' => $current->seat_number,
            ],
        ];
    }
}
