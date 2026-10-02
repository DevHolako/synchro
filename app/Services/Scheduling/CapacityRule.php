<?php

namespace App\Services\Scheduling;

use App\Enums\BookingType;
use App\Enums\ConflictType;
use App\Models\Room;
use App\Models\StudentGroup;

/**
 * A course session's groups, by combined expected headcount, must fit its room's course capacity. One query.
 */
class CapacityRule implements SoftConflictRule
{
    public function softConflicts(BookingSlot $slot): array
    {
        if ($slot->type !== BookingType::CourseSession || $slot->roomIds === []) {
            return [];
        }

        $rooms = Room::query()
            ->whereKey($slot->roomIds)
            ->select(['id', 'name', 'course_capacity'])
            ->addSelect(['headcount' => StudentGroup::query()
                ->selectRaw('coalesce(sum(expected_headcount), 0)')
                ->whereKey($slot->groupIds),
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (Room $room): bool => (int) $room->getAttribute('headcount') > $room->course_capacity);

        return array_values($rooms->map(fn (Room $room): Conflict => new Conflict(
            type: ConflictType::Capacity,
            resourceId: $room->id,
            resourceName: $room->name,
            bookingType: null,
            bookingId: null,
            startsAt: $slot->startsAt,
            endsAt: $slot->endsAt,
            details: ['capacity' => $room->course_capacity, 'headcount' => (int) $room->getAttribute('headcount')],
        ))->all());
    }
}
