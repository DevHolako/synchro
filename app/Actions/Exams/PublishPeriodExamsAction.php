<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\User;
use App\Support\SchoolClock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishPeriodExamsAction
{
    public function __construct(private QueueExamDocumentsAction $queueDocuments) {}

    /**
     * Publish every scheduled exam of the period that has not started yet and has a lead
     * invigilator in each room, in one update, and queue their documents. The others stay scheduled.
     *
     * @return array{published: int, skipped: int} How many exams were published, and how many
     *                                             were left out for want of a lead invigilator.
     *
     * @throws ValidationException When none was left to publish.
     */
    public function execute(ExamPeriod $period, User $publisher): array
    {
        return DB::transaction(function () use ($period, $publisher): array {
            // Lock every upcoming scheduled exam first: staffing and releases take the same row
            // lock, so the leads read below cannot change before the update.
            $upcoming = array_values(array_map('intval', $period->exams()
                ->where('state', ExamState::Scheduled)
                ->where('starts_at', '>', SchoolClock::now())
                ->lockForUpdate()
                ->pluck('id')
                ->all()));
            $ready = array_values(array_map('intval', Exam::query()->whereKey($upcoming)->staffed()->pluck('id')->all()));
            $skipped = count($upcoming) - count($ready);

            $published = Exam::query()->whereKey($ready)->update([
                'state' => ExamState::Published,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ]);

            if ($published === 0) {
                throw ValidationException::withMessages(['period' => $skipped > 0
                    ? __('messages.exam_period_leads_missing', ['count' => $skipped])
                    : __('messages.exam_period_nothing_to_publish')]);
            }

            $this->queueDocuments->execute($ready);

            return ['published' => $published, 'skipped' => $skipped];
        });
    }
}
