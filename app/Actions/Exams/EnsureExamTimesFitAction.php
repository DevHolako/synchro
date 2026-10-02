<?php

namespace App\Actions\Exams;

use App\Models\ExamPeriod;
use App\Support\SchoolClock;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * An exam's times lie inside its period's dates and have not begun by the school's clock.
 */
class EnsureExamTimesFitAction
{
    /**
     * @throws ValidationException
     */
    public function execute(ExamPeriod $period, string $startsAt): void
    {
        $start = CarbonImmutable::parse($startsAt);
        $day = $start->format('Y-m-d');

        if ($day < $period->start_date->format('Y-m-d') || $day > $period->end_date->format('Y-m-d')) {
            throw ValidationException::withMessages(['starts_at' => __('messages.exam_outside_period', [
                'start' => $period->start_date->format('Y-m-d'),
                'end' => $period->end_date->format('Y-m-d'),
            ])]);
        }

        if ($start->lessThanOrEqualTo(SchoolClock::now())) {
            throw ValidationException::withMessages(['starts_at' => __('messages.exam_in_past')]);
        }
    }
}
