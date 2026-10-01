<?php

namespace App\Actions\CourseSessions;

use App\Models\ConflictOverride;
use App\Services\Scheduling\Conflict;
use App\Services\Scheduling\ConflictResult;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes one immutable audit row per soft conflict the user overrode (ADR 0002).
 */
class RecordConflictOverridesAction
{
    public function execute(Model $schedulable, ConflictResult $result, SoftConflictOverride $override): void
    {
        foreach ($result->softConflicts as $conflict) {
            ConflictOverride::create([
                'user_id' => $override->user->id,
                'schedulable_type' => $schedulable->getMorphClass(),
                'schedulable_id' => $schedulable->getKey(),
                'conflict_type' => $conflict->type,
                'justification' => $override->justification,
                'details' => $this->details($conflict),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function details(Conflict $conflict): array
    {
        return [
            'resource_id' => $conflict->resourceId,
            'resource_name' => $conflict->resourceName,
            'starts_at' => $conflict->startsAt->format('Y-m-d H:i'),
            'ends_at' => $conflict->endsAt->format('Y-m-d H:i'),
            ...$conflict->details,
        ];
    }
}
