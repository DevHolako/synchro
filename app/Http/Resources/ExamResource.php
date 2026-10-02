<?php

namespace App\Http\Resources;

use App\Enums\ExamState;
use App\Enums\InvigilatorRole;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamInvigilator;
use App\Models\ExamRoomAssignment;
use App\Models\StudentGroup;
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
    private const string WALL_CLOCK_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $exam = $this->resource;

        return [
            'id' => $exam->id,
            'exam_period_id' => $exam->exam_period_id,
            'start' => $exam->starts_at->format(self::WALL_CLOCK_FORMAT),
            'end' => $exam->ends_at->format(self::WALL_CLOCK_FORMAT),
            'state' => $exam->state->value,
            'revision' => $exam->revision,
            'last_reschedule_reason' => $exam->reschedules->first()?->reason,
            'is_overdue' => $exam->isOverdue(),
            'has_started' => $exam->hasStarted(),
            'module' => [
                'id' => $exam->module->id,
                'program_id' => $exam->module->program_id,
                'code' => $exam->module->code,
                'name' => $exam->module->name,
                'color_code' => $exam->module->color_code,
            ],
            'groups' => $exam->studentGroups
                ->map(fn (StudentGroup $group): array => ['id' => $group->id, 'name' => $group->name])
                ->values()
                ->all(),
            'rooms' => $exam->roomAssignments
                ->map(fn (ExamRoomAssignment $room): array => [
                    'id' => $room->id,
                    'name' => $room->room->name,
                    'students_count' => $room->allocated_students_count,
                    'first_surname' => $room->first_surname,
                    'last_surname' => $room->last_surname,
                    'has_lead' => $room->invigilators->contains('role', InvigilatorRole::Principal),
                ])
                ->values()
                ->all(),
            // The viewer's own place: their seat as a candidate, their room as an invigilator.
            'my_seat' => $this->seat($exam->candidates->first()),
            'my_invigilation' => $this->invigilation($exam->invigilators->first()),
            // The door lists and attendance sheets: null when the viewer may not download them.
            'roster' => $this->roster($request, $exam),
        ];
    }

    /**
     * @return array{room: string, seat: int, convocation_ready: bool}|null
     */
    private function seat(?ExamCandidate $candidate): ?array
    {
        return $candidate === null ? null : [
            'room' => $candidate->roomAssignment->room->name,
            'seat' => $candidate->seat_number,
            'convocation_ready' => Storage::disk('local')->exists($candidate->convocationPath()),
        ];
    }

    /**
     * @return 'ready'|'pending'|null
     */
    private function roster(Request $request, Exam $exam): ?string
    {
        if (! in_array($exam->state, ExamState::visibleToCandidates(), true) || ! ($request->user()?->can('downloadRoster', $exam) ?? false)) {
            return null;
        }

        return Storage::disk('local')->exists($exam->rosterPath()) ? 'ready' : 'pending';
    }

    /**
     * @return array{assignment_id: int, room: string, role: string}|null
     */
    private function invigilation(?ExamInvigilator $invigilator): ?array
    {
        return $invigilator === null ? null : [
            'assignment_id' => $invigilator->exam_room_assignment_id,
            'room' => $invigilator->roomAssignment->room->name,
            'role' => $invigilator->role->value,
        ];
    }
}
