<?php

namespace App\Actions\Exams;

use App\Models\ExamPeriod;
use Illuminate\Validation\ValidationException;

class DeleteExamPeriodAction
{
    /**
     * Remove a period that holds no exams.
     *
     * @throws ValidationException When exams remain in it.
     */
    public function execute(ExamPeriod $period): void
    {
        if ($period->exams()->exists()) {
            throw ValidationException::withMessages(['period' => __('messages.exam_period_has_exams')]);
        }

        $period->delete();
    }
}
