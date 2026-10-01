<?php

namespace App\Services\Scheduling;

use App\Enums\ConflictType;
use App\Models\TeacherUnavailability;

/**
 * The teacher has declared (pending or approved) that they are unavailable during the slot. One query.
 */
class TeacherUnavailabilityRule implements SoftConflictRule
{
    public function softConflicts(SessionSlot $slot): array
    {
        $unavailabilities = TeacherUnavailability::query()
            ->join('users', 'users.id', '=', 'teacher_unavailabilities.teacher_id')
            ->where('teacher_unavailabilities.teacher_id', $slot->teacherId)
            ->overlappingSession($slot->startsAt, $slot->endsAt)
            ->orderBy('teacher_unavailabilities.id')
            ->get(['teacher_unavailabilities.*', 'users.name as teacher_name']);

        return array_values($unavailabilities->map(fn (TeacherUnavailability $unavailability): Conflict => new Conflict(
            type: ConflictType::Unavailability,
            resourceId: $slot->teacherId,
            resourceName: (string) $unavailability->getAttribute('teacher_name'),
            sessionId: null,
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
