<?php

namespace App\Actions\Exams;

use App\Models\ExamPeriod;
use App\Support\SchoolClock;
use Illuminate\Database\Eloquent\Collection;

/**
 * Every exam period, latest first, with what its status needs; and which one to open by default.
 */
class ListExamPeriodsAction
{
    /**
     * @return Collection<int, ExamPeriod>
     */
    public function execute(): Collection
    {
        return ExamPeriod::query()->withStatusCounts()->orderByDesc('start_date')->orderByDesc('id')->get();
    }

    /**
     * The period to open when none is asked for: the earliest not yet ended, otherwise the latest.
     *
     * @param  Collection<int, ExamPeriod>  $periods
     */
    public function current(Collection $periods): ?ExamPeriod
    {
        $today = SchoolClock::today()->format('Y-m-d');

        return $periods
            ->filter(fn (ExamPeriod $period): bool => $period->end_date->format('Y-m-d') >= $today)
            ->sortBy(fn (ExamPeriod $period): string => $period->start_date->format('Y-m-d'))
            ->first() ?? $periods->first();
    }
}
