<?php

namespace App\Http\Resources;

use App\Enums\InvigilatorRole;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamInvigilator;
use App\Models\ExamRoomAssignment;
use App\Models\StudentGroup;
use App\Support\SchoolClock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An exam for the exams page. Times are offset-less wall-clock times, as on the timetable.
 *
 * @property Exam $resource
 */
class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $exam = $this->resource;

        return [
            'id' => $exam->id,
            'exam_period_id' => $exam->exam_period_id,
            'start' => $exam->starts_at->format(SchoolClock::WALL_CLOCK_FORMAT),
            'end' => $exam->ends_at->format(SchoolClock::WALL_CLOCK_FORMAT),
            'state' => $exam->state->value,
            'revision' => $exam->revision,
            'last_reschedule_reason' => $exam->reschedules->first()?->reason,
            'is_editable' => $exam->state->isEditable(),
            'is_overdue' => $exam->isOverdue(),
            'has_started' => $exam->hasStarted(),
            'module' => [
                'id' => $exam->module->id,
                'program_id' => $exam->module->program_id,
                'code' => $exam->module->code,
                'name' => $exam->module->name,
                'label' => $exam->module->label(),
                'color_code' => $exam->module->color_code,
            ],
            'groups' => $exam->studentGroups
                ->map(fn (StudentGroup $group): array => ['id' => $group->id, 'name' => $group->name])
                ->values()
                ->all(),
            'rooms' => $exam->roomAssignments
                ->map(fn (ExamRoomAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'name' => $assignment->room->name,
                    'students_count' => $assignment->allocated_students_count,
                    'first_surname' => $assignment->first_surname,
                    'last_surname' => $assignment->last_surname,
                    'has_lead' => $assignment->invigilators->contains('role', InvigilatorRole::Principal),
                ])
                ->values()
                ->all(),
            // The viewer's own place: their seat as a candidate, their room as an invigilator.
            'my_seat' => $this->seat($exam, $exam->candidates->first()),
            'my_invigilation' => $this->invigilation($request, $exam, $exam->invigilators->first()),
            // The door lists and attendance sheets: null when the viewer may not download them.
            'roster' => $this->roster($request, $exam),
        ];
    }

    /**
     * The convocation exists only once the exam is published (null before).
     *
     * @return array{room: string, seat: int, convocation: 'ready'|'pending'|null}|null
     */
    private function seat(Exam $exam, ?ExamCandidate $candidate): ?array
    {
        if ($candidate === null) {
            return null;
        }

        $convocation = null;

        if ($exam->state->isVisibleToCandidates()) {
            $convocation = Storage::disk('local')->exists($candidate->convocationPath()) ? 'ready' : 'pending';
        }

        return [
            'room' => $candidate->roomAssignment->room->name,
            'seat' => $candidate->seat_number,
            'convocation' => $convocation,
        ];
    }

    /**
     * The permission check reads the loaded invigilators, so it adds no query per row.
     *
     * @return 'ready'|'pending'|null
     */
    private function roster(Request $request, Exam $exam): ?string
    {
        if (! $exam->state->isVisibleToCandidates() || ! ($request->user()?->can('downloadRoster', $exam) ?? false)) {
            return null;
        }

        return Storage::disk('local')->exists($exam->rosterPath()) ? 'ready' : 'pending';
    }

    /**
     * @return array{assignment_id: int, room: string, role: string, can_check_in: bool}|null
     */
    private function invigilation(Request $request, Exam $exam, ?ExamInvigilator $invigilator): ?array
    {
        return $invigilator === null ? null : [
            'assignment_id' => $invigilator->exam_room_assignment_id,
            'room' => $invigilator->roomAssignment->room->name,
            'role' => $invigilator->role->value,
            // Assigned is not enough: checking in also takes the RecordAttendance permission.
            'can_check_in' => $request->user()?->can('checkIn', $exam) ?? false,
        ];
    }
}
