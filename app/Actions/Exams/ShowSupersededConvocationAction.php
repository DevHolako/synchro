<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use App\Models\SupersededConvocation;

/**
 * What scanning a superseded convocation shows: a warning, and where the student now sits.
 */
class ShowSupersededConvocationAction
{
    private const string WALL_CLOCK_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @return array<string, mixed>
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
            'student' => $superseded->student()->value('name'),
            'exam' => [
                'module' => "{$exam->module->code} · {$exam->module->name}",
                'start' => $exam->starts_at->format(self::WALL_CLOCK_FORMAT),
                'end' => $exam->ends_at->format(self::WALL_CLOCK_FORMAT),
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
