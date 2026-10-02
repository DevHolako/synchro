<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use App\Support\SchoolClock;

class CompleteEndedExamsAction
{
    /**
     * Mark published exams that have ended, by the school's clock, as Completed. Safe to run repeatedly.
     *
     * @return int How many exams were completed.
     */
    public function execute(): int
    {
        return Exam::query()
            ->where('state', ExamState::Published)
            ->where('ends_at', '<=', SchoolClock::now())
            ->update(['state' => ExamState::Completed]);
    }
}
