<?php

namespace App\Actions\Exams;

use App\Actions\Users\ListTeacherOptionsAction;
use App\Enums\ExamState;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamInvigilator;
use App\Models\ExamRoomAssignment;
use App\Models\Room;
use App\Models\StudentProfile;

/**
 * What the rooms and invigilators sheet shows for an exam: how many students sit it, its rooms
 * in order with their split and staff, the active rooms (busy ones flagged), and the teachers.
 */
class ShowExamAllocationAction
{
    /** Above this many students in a room, an assistant invigilator is recommended (spec 04). */
    public const int ASSISTANT_THRESHOLD = 25;

    public function __construct(private ListTeacherOptionsAction $listTeachers) {}

    /**
     * @return array{
     *     state: string,
     *     rooms_editable: bool,
     *     force_single_room: bool,
     *     students_count: int,
     *     assistant_threshold: int,
     *     assignments: list<array{id: int, room_id: int, room: string, building: string, exam_capacity: int, allocated_students_count: int, first_surname: string|null, last_surname: string|null, invigilators: list<array{teacher_id: int, name: string, role: string}>}>,
     *     rooms: list<array{id: int, name: string, building: string, exam_capacity: int, busy: bool}>,
     *     teachers: list<array{id: int, name: string}>
     * }
     */
    public function execute(Exam $exam): array
    {
        $assignments = $exam->roomAssignments()
            ->with(['room:id,building_id,name,exam_capacity', 'room.building:id,name', 'invigilators.teacher:id,name'])
            ->get();

        return [
            'state' => $exam->state->value,
            // Rooms (and so seats) are frozen from publication on.
            'rooms_editable' => $exam->state->isEditable(),
            'force_single_room' => $exam->force_single_room,
            'students_count' => StudentProfile::query()
                ->whereIn('student_group_id', $exam->studentGroups()->select('student_groups.id'))
                ->count(),
            'assistant_threshold' => self::ASSISTANT_THRESHOLD,
            'assignments' => array_values($assignments->map(fn (ExamRoomAssignment $assignment): array => [
                'id' => $assignment->id,
                'room_id' => $assignment->room_id,
                'room' => $assignment->room->name,
                'building' => $assignment->room->building->name,
                'exam_capacity' => $assignment->room->exam_capacity,
                'allocated_students_count' => $assignment->allocated_students_count,
                'first_surname' => $assignment->first_surname,
                'last_surname' => $assignment->last_surname,
                'invigilators' => array_values($assignment->invigilators->map(fn (ExamInvigilator $invigilator): array => [
                    'teacher_id' => $invigilator->teacher_id,
                    'name' => $invigilator->teacher->name,
                    'role' => $invigilator->role->value,
                ])->all()),
            ])->all()),
            'rooms' => $this->rooms($exam),
            'teachers' => $this->listTeachers->execute(),
        ];
    }

    /**
     * Active rooms by building and name, flagged when a course session or another booked exam
     * holds them during the exam.
     *
     * @return list<array{id: int, name: string, building: string, exam_capacity: int, busy: bool}>
     */
    private function rooms(Exam $exam): array
    {
        $start = $exam->starts_at->toImmutable();
        $end = $exam->ends_at->toImmutable();

        $busy = CourseSession::query()->overlapping($start, $end)->pluck('room_id')
            ->merge(ExamRoomAssignment::query()
                ->whereHas('exam', fn ($exams) => $exams
                    ->overlapping($start, $end)
                    ->whereIn('state', ExamState::occupying())
                    ->whereKeyNot($exam->id))
                ->pluck('room_id'))
            ->flip();

        return array_values(Room::query()
            ->active()
            ->with('building:id,name')
            ->get(['id', 'building_id', 'name', 'exam_capacity'])
            ->sortBy([['building.name', 'asc'], ['name', 'asc']])
            ->map(fn (Room $room): array => [
                'id' => $room->id,
                'name' => $room->name,
                'building' => $room->building->name,
                'exam_capacity' => $room->exam_capacity,
                'busy' => $busy->has($room->id),
            ])
            ->all());
    }
}
