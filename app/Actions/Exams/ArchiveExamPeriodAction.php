<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\ExamPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveExamPeriodAction
{
    /**
     * Archive a finished period: every completed exam moves to Archived.
     *
     * @return int How many exams were archived.
     *
     * @throws ValidationException When some exam is not completed yet, or nothing is left to archive.
     */
    public function execute(ExamPeriod $period): int
    {
        return DB::transaction(function () use ($period): int {
            ExamPeriod::query()->whereKey($period->id)->lockForUpdate()->first();

            $unfinished = $period->exams()
                ->whereIn('state', [ExamState::Draft, ExamState::Scheduled, ExamState::Published])
                ->exists();

            if ($unfinished) {
                throw ValidationException::withMessages(['period' => __('messages.exam_period_not_archivable')]);
            }

            $archived = $period->exams()->where('state', ExamState::Completed)->update(['state' => ExamState::Archived]);

            if ($archived === 0) {
                throw ValidationException::withMessages(['period' => __('messages.exam_period_nothing_to_archive')]);
            }

            return $archived;
        });
    }
}
