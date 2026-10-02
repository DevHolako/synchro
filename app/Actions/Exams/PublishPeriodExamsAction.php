<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\ExamPeriod;
use App\Models\User;
use App\Support\SchoolClock;
use Illuminate\Validation\ValidationException;

class PublishPeriodExamsAction
{
    /**
     * Publish every scheduled exam of the period that has not started yet, in one update.
     *
     * @return int How many exams were published.
     *
     * @throws ValidationException When none was left to publish.
     */
    public function execute(ExamPeriod $period, User $publisher): int
    {
        $published = $period->exams()
            ->where('state', ExamState::Scheduled)
            ->where('starts_at', '>', SchoolClock::now())
            ->update([
                'state' => ExamState::Published,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ]);

        if ($published === 0) {
            throw ValidationException::withMessages(['period' => __('messages.exam_period_nothing_to_publish')]);
        }

        return $published;
    }
}
