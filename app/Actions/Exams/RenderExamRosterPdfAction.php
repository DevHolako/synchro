<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamRoomAssignment;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * An exam's paper backup (in French): for each room, the door list and the attendance sheet
 * (feuille d'émargement), students in seat order. Invigilators sign blank boxes, so a change
 * of invigilator never makes the document stale.
 */
class RenderExamRosterPdfAction
{
    /**
     * @return string The PDF document.
     */
    public function execute(Exam $exam): string
    {
        $assignments = $exam->roomAssignments()
            ->with([
                'room.building:id,name',
                'candidates' => fn ($candidates) => $candidates->orderBy('seat_number'),
                'candidates.student.studentProfile.studentGroup:id,name',
            ])
            ->get();

        return Pdf::loadView('pdf.exam-roster', [
            'exam' => $exam->loadMissing('module:id,code,name'),
            'day' => $exam->starts_at->settings(['locale' => 'fr'])->isoFormat('dddd D MMMM YYYY'),
            'rooms' => $assignments->map(fn (ExamRoomAssignment $assignment): array => [
                'name' => "{$assignment->room->name} · {$assignment->room->building->name}",
                'from' => $assignment->first_surname,
                'to' => $assignment->last_surname,
                'students' => $assignment->candidates->map(fn (ExamCandidate $candidate): array => [
                    'seat' => $candidate->seat_number,
                    'name' => $candidate->student->officialName(),
                    'student_number' => $candidate->student->studentProfile->student_number ?? '',
                    'group' => $candidate->student->studentProfile?->studentGroup->name ?? '',
                ])->all(),
            ])->all(),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output();
    }
}
