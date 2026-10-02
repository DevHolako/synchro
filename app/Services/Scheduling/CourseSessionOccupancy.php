<?php

namespace App\Services\Scheduling;

use App\Enums\BookingType;
use App\Enums\ConflictType;
use App\Models\CourseSession;
use Illuminate\Database\Eloquent\Builder;

/**
 * Course sessions as an occupancy source: one indexed query per resource type.
 */
class CourseSessionOccupancy implements OccupancySource
{
    public function hardConflicts(BookingSlot $slot): array
    {
        return [
            ...$this->collisions($slot, ConflictType::Room, 'rooms', 'course_sessions.room_id', $slot->roomIds),
            ...$this->collisions($slot, ConflictType::Teacher, 'users', 'course_sessions.teacher_id', $slot->teacherIds),
            ...$this->groupCollisions($slot),
        ];
    }

    /**
     * Overlapping sessions booking the same room or teacher, with that resource's name.
     *
     * @param  list<int>  $resourceIds
     * @return list<Conflict>
     */
    private function collisions(BookingSlot $slot, ConflictType $type, string $table, string $column, array $resourceIds): array
    {
        if ($resourceIds === []) {
            return [];
        }

        $rows = $this->overlapping($slot)
            ->join($table, "{$table}.id", '=', $column)
            ->whereIn($column, $resourceIds)
            ->get(['course_sessions.id', 'course_sessions.starts_at', 'course_sessions.ends_at', "{$table}.id as resource_id", "{$table}.name as resource_name"]);

        return $this->toConflicts($rows->all(), $type);
    }

    /**
     * Overlapping sessions serving any of the slot's groups, one conflict per shared group.
     *
     * @return list<Conflict>
     */
    private function groupCollisions(BookingSlot $slot): array
    {
        if ($slot->groupIds === []) {
            return [];
        }

        $rows = $this->overlapping($slot)
            ->join('course_session_student_group as pivot', 'pivot.course_session_id', '=', 'course_sessions.id')
            ->join('student_groups', 'student_groups.id', '=', 'pivot.student_group_id')
            ->whereIn('pivot.student_group_id', $slot->groupIds)
            ->get(['course_sessions.id', 'course_sessions.starts_at', 'course_sessions.ends_at', 'student_groups.id as resource_id', 'student_groups.name as resource_name']);

        return $this->toConflicts($rows->all(), ConflictType::Group);
    }

    /**
     * @return Builder<CourseSession>
     */
    private function overlapping(BookingSlot $slot): Builder
    {
        return CourseSession::query()
            ->overlapping($slot->startsAt, $slot->endsAt)
            ->when($slot->ignoredIdOf(BookingType::CourseSession), fn (Builder $query, int $id) => $query->where('course_sessions.id', '!=', $id))
            ->orderBy('course_sessions.starts_at');
    }

    /**
     * @param  array<int, CourseSession>  $rows
     * @return list<Conflict>
     */
    private function toConflicts(array $rows, ConflictType $type): array
    {
        return array_values(array_map(fn (CourseSession $row): Conflict => new Conflict(
            type: $type,
            resourceId: (int) $row->getAttribute('resource_id'),
            resourceName: (string) $row->getAttribute('resource_name'),
            bookingType: BookingType::CourseSession,
            bookingId: $row->id,
            startsAt: $row->starts_at,
            endsAt: $row->ends_at,
        ), $rows));
    }
}
