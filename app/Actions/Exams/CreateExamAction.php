<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class CreateExamAction
{
    public function __construct(private SaveExamAction $save) {}

    /**
     * Draft an exam: it books nothing until it is scheduled.
     *
     * @param  array{exam_period_id: int, module_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Exam
    {
        return $this->save->execute(new Exam, $data);
    }
}
