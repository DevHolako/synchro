<?php

namespace App\Services\Scheduling;

use App\Enums\ConflictType;
use App\Models\TeacherUnavailability;

/**
 * A teacher of the slot has declared (pending or approved) that they are unavailable during it. One query.
 */
class TeacherUnavailabilityRule implements SoftConflictRule
{
    public function softConflicts(SessionSlot $slot): array
    {
        if ($slot->teacherIds === []) {
            return [];
        }

        $unavailabilities = TeacherUnavailability::query()
            ->join('users', 'users.id', '=', 'teacher_unavailabilities.teacher_id')
            ->whereIn('teacher_unavailabilities.teacher_id', $slot->teacherIds)
            ->overlappingSession($slot->startsAt, $slot->endsAt)
            ->orderBy('teacher_unavailabilities.id')
            ->get(['teacher_unavailabilities.*', 'users.name as teacher_name']);

        return array_values($unavailabilities->map(fn (TeacherUnavailability $unavailability): Conflict => new Conflict(
            type: ConflictType::Unavailability,
            resourceId: $unavailability->teacher_id,
            resourceName: (string) $unavailability->getAttribute('teacher_name'),
            bookingType: null,
            bookingId: null,
            startsAt: $slot->startsAt,
            endsAt: $slot->endsAt,
            details: [
                'unavailability_id' => $unavailability->id,
                'unavailability_type' => $unavailability->type->value,
                'status' => $unavailability->status->value,
                'reason' => $unavailability->reason,
            ],
        ))->all());
    }
}
