<?php

namespace App\Http\Requests\Exams;

use App\Models\ExamPeriod;

class UpdateExamPeriodRequest extends StoreExamPeriodRequest
{
    public function authorize(): bool
    {
        $period = $this->route('exam_period');

        return $period instanceof ExamPeriod && ($this->user()?->can('update', $period) ?? false);
    }
}
