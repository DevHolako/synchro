<?php

namespace App\Actions\Exams;

use App\Models\ExamPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateExamPeriodAction
{
    /**
     * Rename or re-date a period, refusing new dates that would leave some of its exams outside.
     *
     * @param  array{name: string, session_type: string, academic_year: string, start_date: string, end_date: string}  $data
     *
     * @throws ValidationException
     */
    public function execute(ExamPeriod $period, array $data): ExamPeriod
    {
        return DB::transaction(function () use ($period, $data): ExamPeriod {
            // Exams are saved under the same lock, so none can slip outside the new dates meanwhile.
            ExamPeriod::query()->whereKey($period->id)->lockForUpdate()->first();

            $dayAfterEnd = CarbonImmutable::parse($data['end_date'])->addDay()->format('Y-m-d');

            $excludesExams = $period->exams()
                ->where(fn ($query) => $query->where('starts_at', '<', $data['start_date'])->orWhere('starts_at', '>=', $dayAfterEnd))
                ->exists();

            if ($excludesExams) {
                throw ValidationException::withMessages(['start_date' => __('messages.exam_period_excludes_exams')]);
            }

            $period->update($data);

            return $period;
        });
    }
}
