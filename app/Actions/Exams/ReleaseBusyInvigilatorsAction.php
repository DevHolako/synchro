<?php

namespace App\Actions\Exams;

use App\Enums\ConflictType;
use App\Models\ConflictOverride;
use App\Models\Exam;
use App\Models\User;
use App\Services\Scheduling\Conflict;
use App\Services\Scheduling\ConflictDetectorService;

/**
 * When a booked exam takes a new time, frees the invigilators who can no longer watch it: those
 * teaching or invigilating elsewhere then, and those who declared an unavailability then that
 * was not knowingly overridden for this exam. They are named so they can be replaced.
 *
 * Runs inside the caller's transaction, before the exam's conflict guard.
 */
class ReleaseBusyInvigilatorsAction
{
    public function __construct(private ConflictDetectorService $detector) {}

    /**
     * @return array<int, string> The released teachers' names, by id.
     */
    public function execute(Exam $exam): array
    {
        $teacherIds = array_values(array_map('intval', $exam->invigilators()->pluck('teacher_id')->all()));

        if ($teacherIds === []) {
            return [];
        }

        $result = $this->detector->checkConflicts($exam->invigilationSlot($teacherIds));
        $overridden = $this->overriddenUnavailabilityIds($exam);

        $busy = array_merge(
            array_filter($result->hardConflicts, fn (Conflict $conflict): bool => $conflict->type === ConflictType::Teacher),
            array_filter($result->softConflicts, fn (Conflict $conflict): bool => $conflict->type === ConflictType::Unavailability
                && ! in_array($conflict->details['unavailability_id'] ?? null, $overridden, true)),
        );
        $busyIds = array_values(array_unique(array_map(fn (Conflict $conflict): int => $conflict->resourceId, $busy)));

        if ($busyIds === []) {
            return [];
        }

        $exam->invigilators()->whereIn('teacher_id', $busyIds)->delete();

        return User::query()->whereKey($busyIds)->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Unavailabilities already overridden, with a justification, when staffing this exam.
     *
     * @return list<int>
     */
    private function overriddenUnavailabilityIds(Exam $exam): array
    {
        return array_values(ConflictOverride::query()
            ->where('schedulable_type', $exam->getMorphClass())
            ->where('schedulable_id', $exam->id)
            ->where('conflict_type', ConflictType::Unavailability)
            ->get(['details'])
            ->map(fn (ConflictOverride $override): int => (int) ($override->details['unavailability_id'] ?? 0))
            ->all());
    }
}
