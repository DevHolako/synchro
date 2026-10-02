<?php

namespace App\Services\Scheduling;

use App\Enums\BookingType;
use App\Enums\ConflictType;
use App\Enums\ExamState;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exams past the draft stage as an occupancy source: they book their rooms, invigilators and
 * groups (ADR 0002). Drafts are a sandbox and book nothing. One indexed query per resource type.
 */
class ExamOccupancy implements OccupancySource
{
    public function hardConflicts(BookingSlot $slot): array
    {
        return [
            ...$this->collisions($slot, ConflictType::Room, $slot->roomIds, 'exam_room_assignments', 'room_id', 'rooms'),
            ...$this->collisions($slot, ConflictType::Teacher, $slot->teacherIds, 'exam_invigilators', 'teacher_id', 'users'),
            ...$this->collisions($slot, ConflictType::Group, $slot->groupIds, 'exam_student_group', 'student_group_id', 'student_groups'),
        ];
    }

    /**
     * Overlapping booked exams holding any of the resources, one conflict per shared resource.
     *
     * @param  list<int>  $resourceIds
     * @param  string  $link  The table tying exams to the resource (by `exam_id`).
     * @return list<Conflict>
     */
    private function collisions(BookingSlot $slot, ConflictType $type, array $resourceIds, string $link, string $column, string $table): array
    {
        if ($resourceIds === []) {
            return [];
        }

        $rows = Exam::query()
            ->overlapping($slot->startsAt, $slot->endsAt)
            ->whereIn('exams.state', ExamState::occupying())
            ->when($slot->ignoredIdOf(BookingType::Exam), fn (Builder $query, int $id) => $query->where('exams.id', '!=', $id))
            ->join("{$link} as link", 'link.exam_id', '=', 'exams.id')
            ->join($table, "{$table}.id", '=', "link.{$column}")
            ->whereIn("link.{$column}", $resourceIds)
            ->orderBy('exams.starts_at')
            ->get(['exams.id', 'exams.starts_at', 'exams.ends_at', "{$table}.id as resource_id", "{$table}.name as resource_name"]);

        return array_values($rows->map(fn (Exam $row): Conflict => new Conflict(
            type: $type,
            resourceId: (int) $row->getAttribute('resource_id'),
            resourceName: (string) $row->getAttribute('resource_name'),
            bookingType: BookingType::Exam,
            bookingId: $row->id,
            startsAt: $row->starts_at,
            endsAt: $row->ends_at,
        ))->all());
    }
}
