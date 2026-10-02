<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Enums\InvigilatorRole;
use App\Models\ExamPeriod;
use App\Models\User;
use App\Support\SchoolClock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PublishPeriodExamsAction
{
    /**
     * Publish every scheduled exam of the period that has not started yet and has a lead
     * invigilator in each room, in one update. The others stay scheduled.
     *
     * @return array{published: int, skipped: int} How many exams were published, and how many
     *                                             were left out for want of a lead invigilator.
     *
     * @throws ValidationException When none was left to publish.
     */
    public function execute(ExamPeriod $period, User $publisher): array
    {
        $upcoming = fn () => $period->exams()
            ->where('state', ExamState::Scheduled)
            ->where('starts_at', '>', SchoolClock::now());

        $withoutLead = fn (Builder $rooms) => $rooms
            ->whereDoesntHave('invigilators', fn (Builder $invigilators) => $invigilators->where('role', InvigilatorRole::Principal));

        $skipped = $upcoming()->whereHas('roomAssignments', $withoutLead)->count();

        $published = $upcoming()
            ->whereDoesntHave('roomAssignments', $withoutLead)
            ->update([
                'state' => ExamState::Published,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ]);

        if ($published === 0) {
            throw ValidationException::withMessages(['period' => $skipped > 0
                ? __('messages.exam_period_leads_missing', ['count' => $skipped])
                : __('messages.exam_period_nothing_to_publish')]);
        }

        return ['published' => $published, 'skipped' => $skipped];
    }
}
