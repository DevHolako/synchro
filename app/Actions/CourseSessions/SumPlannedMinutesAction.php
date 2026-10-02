<?php

namespace App\Actions\CourseSessions;

use App\Models\CourseSession;
use Illuminate\Database\Eloquent\Builder;

/**
 * Minutes already scheduled per group and module, past and future.
 *
 * A module's syllabus applies to every group taking it, so a session shared by several groups
 * counts in full for each. Durations are summed in PHP to stay portable across databases.
 */
class SumPlannedMinutesAction
{
    /**
     * @param  list<int>  $groupIds
     * @param  int|null  $moduleId  Only this module, or every module when null.
     * @return array<int, array<int, int>> Minutes by group id, then module id.
     */
    public function execute(array $groupIds, ?int $moduleId = null): array
    {
        $planned = array_fill_keys($groupIds, []);

        $sessions = CourseSession::query()
            ->when($moduleId !== null, fn (Builder $query) => $query->where('module_id', $moduleId))
            ->whereHas('studentGroups', fn (Builder $groups) => $groups->whereIn('student_groups.id', $groupIds))
            ->with('studentGroups:student_groups.id')
            ->get(['id', 'module_id', 'starts_at', 'ends_at']);

        foreach ($sessions as $session) {
            foreach ($session->studentGroups as $group) {
                if (isset($planned[$group->id])) {
                    $planned[$group->id][$session->module_id] = ($planned[$group->id][$session->module_id] ?? 0)
                        + $session->durationInMinutes();
                }
            }
        }

        return $planned;
    }
}
