<?php

namespace App\Http\Requests\Exams;

use App\Models\Exam;

class UpdateExamRequest extends StoreExamRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam && ($this->user()?->can('update', $exam) ?? false);
    }
}
