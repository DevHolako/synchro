<?php

namespace App\Actions\CourseSessions;

use App\Models\Module;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Builder;

/**
 * Planned hours against syllabus hours for each module of a group's program.
 */
class CalculateSyllabusProgressAction
{
    public function __construct(private SumPlannedMinutesAction $sumPlannedMinutes) {}

    /**
     * Active modules, plus inactive ones that already have sessions for the group; null for an unknown group.
     *
     * @return list<array{module_id: int, code: string, name: string, color_code: string, total_hours: int, planned_minutes: int}>|null
     */
    public function execute(int $groupId): ?array
    {
        $group = StudentGroup::query()->find($groupId);

        if ($group === null) {
            return null;
        }

        $plannedMinutes = $this->sumPlannedMinutes->execute([$group->id])[$group->id];

        return array_values(Module::query()
            ->forProgram($group->program_id)
            ->where(fn (Builder $modules) => $modules->where('is_active', true)->orWhereIn('id', array_keys($plannedMinutes)))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'color_code', 'total_hours'])
            ->map(fn (Module $module): array => [
                'module_id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'color_code' => $module->color_code,
                'total_hours' => $module->total_hours,
                'planned_minutes' => $plannedMinutes[$module->id] ?? 0,
            ])
            ->all());
    }
}
