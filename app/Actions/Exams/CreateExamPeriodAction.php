<?php

namespace App\Actions\Exams;

use App\Models\ExamPeriod;

class CreateExamPeriodAction
{
    /**
     * @param  array{name: string, session_type: string, academic_year: string, start_date: string, end_date: string}  $data
     */
    public function execute(array $data): ExamPeriod
    {
        return ExamPeriod::create($data);
    }
}
