<?php

namespace App\Services\Scheduling;

use App\Enums\ConflictType;
use App\Models\Room;
use App\Models\StudentGroup;

/**
 * The groups' combined expected headcount must fit the room's course capacity. One query.
 */
class CapacityRule implements SoftConflictRule
{
    public function softConflicts(SessionSlot $slot): array
    {
        $room = Room::query()
            ->whereKey($slot->roomId)
            ->select(['id', 'name', 'course_capacity'])
            ->addSelect(['headcount' => StudentGroup::query()
                ->selectRaw('coalesce(sum(expected_headcount), 0)')
                ->whereKey($slot->groupIds),
            ])
            ->first();

        if ($room === null) {
            return [];
        }

        $headcount = (int) $room->getAttribute('headcount');

        if ($headcount <= $room->course_capacity) {
            return [];
        }

        return [new Conflict(
            type: ConflictType::Capacity,
            resourceId: $room->id,
            resourceName: $room->name,
            sessionId: null,
            startsAt: $slot->startsAt,
            endsAt: $slot->endsAt,
            details: ['capacity' => $room->course_capacity, 'headcount' => $headcount],
        )];
    }
}
