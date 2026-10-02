<?php

namespace App\Services\Scheduling;

use App\Enums\BookingType;
use App\Enums\ConflictType;
use App\Enums\ExamState;
use App\Models\Exam;

/**
 * Exams past the draft stage as an occupancy source: they book their groups (ADR 0002).
 *
 * Drafts are a sandbox and book nothing; rooms and invigilators join with room allocation.
 */
class ExamOccupancy implements OccupancySource
{
    public function hardConflicts(SessionSlot $slot): array
    {
        if ($slot->groupIds === []) {
            return [];
        }

        $rows = Exam::query()
            ->overlapping($slot->startsAt, $slot->endsAt)
            ->whereIn('exams.state', ExamState::occupying())
            ->when($slot->ignoredIdOf(BookingType::Exam), fn ($query, int $id) => $query->where('exams.id', '!=', $id))
            ->join('exam_student_group as pivot', 'pivot.exam_id', '=', 'exams.id')
            ->join('student_groups', 'student_groups.id', '=', 'pivot.student_group_id')
            ->whereIn('pivot.student_group_id', $slot->groupIds)
            ->orderBy('exams.starts_at')
            ->get(['exams.id', 'exams.starts_at', 'exams.ends_at', 'student_groups.id as resource_id', 'student_groups.name as resource_name']);

        return array_values($rows->map(fn (Exam $row): Conflict => new Conflict(
            type: ConflictType::Group,
            resourceId: (int) $row->getAttribute('resource_id'),
            resourceName: (string) $row->getAttribute('resource_name'),
            bookingType: BookingType::Exam,
            bookingId: $row->id,
            startsAt: $row->starts_at,
            endsAt: $row->ends_at,
        ))->all());
    }
}
