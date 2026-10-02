<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * A period's exams as one viewer may see them (every state for exam managers, otherwise only
 * published exams concerning them), with per-state counts.
 */
class ListExamsAction
{
    /**
     * @param  array{state: ExamState|null, program_id: int|null, group_id: int|null}  $filters
     * @return array{exams: Collection<int, Exam>, stats: array<string, int>}
     */
    public function execute(User $viewer, ExamPeriod $period, array $filters): array
    {
        $visible = fn (): Builder => Exam::query()->visibleTo($viewer)->where('exam_period_id', $period->id);

        $exams = $visible()
            ->with(['module:id,program_id,code,name,color_code', 'studentGroups:id,name,code'])
            ->when($filters['state'], fn (Builder $query, ExamState $state) => $query->where('state', $state))
            ->when($filters['program_id'], fn (Builder $query, int $programId) => $query
                ->whereHas('module', fn (Builder $modules) => $modules->where('program_id', $programId)))
            ->when($filters['group_id'], fn (Builder $query, int $groupId) => $query
                ->whereHas('studentGroups', fn (Builder $groups) => $groups->whereKey($groupId)))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $counts = $visible()->selectRaw('state, count(*) as total')->groupBy('state')->pluck('total', 'state');

        $stats = [];

        foreach (ExamState::cases() as $state) {
            $stats[$state->value] = (int) ($counts[$state->value] ?? 0);
        }

        return ['exams' => $exams, 'stats' => $stats];
    }
}
