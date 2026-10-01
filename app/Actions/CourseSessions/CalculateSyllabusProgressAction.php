<?php

namespace App\Actions\CourseSessions;

use App\Models\CourseSession;
use App\Models\Module;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Planned hours against syllabus hours for each module of a group's program.
 *
 * A module's syllabus applies to every group taking it, so hours are counted per group:
 * every session (past or future) including the group counts in full, even when shared
 * with other groups. Durations are summed in PHP to stay portable across databases.
 */
class CalculateSyllabusProgressAction
{
    /**
     * Active modules, plus inactive ones that already have sessions for the group.
     *
     * @return list<array{module_id: int, code: string, name: string, color_code: string, total_hours: int, planned_minutes: int}>
     */
    public function execute(StudentGroup $group): array
    {
        /** @var Collection<int, int> $plannedMinutes */
        $plannedMinutes = CourseSession::query()
            ->whereHas('studentGroups', fn (Builder $groups) => $groups->where('student_groups.id', $group->id))
            ->get(['module_id', 'starts_at', 'ends_at'])
            ->groupBy('module_id')
            ->map(fn (Collection $sessions): int => $sessions->sum(
                fn (CourseSession $session): int => (int) $session->starts_at->diffInMinutes($session->ends_at),
            ));

        return array_values(Module::query()
            ->forProgram($group->program_id)
            ->where(fn (Builder $modules) => $modules->where('is_active', true)->orWhereIn('id', $plannedMinutes->keys()))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'color_code', 'total_hours'])
            ->map(fn (Module $module): array => [
                'module_id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'color_code' => $module->color_code,
                'total_hours' => $module->total_hours,
                'planned_minutes' => $plannedMinutes->get($module->id, 0),
            ])
            ->all());
    }
}
