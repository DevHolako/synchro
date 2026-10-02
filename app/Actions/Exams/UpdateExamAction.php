<?php

namespace App\Actions\Exams;

use App\Exceptions\HardConflictException;
use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class UpdateExamAction
{
    public function __construct(private SaveExamAction $save) {}

    /**
     * Edit a draft or scheduled exam; a scheduled one is checked afresh for conflicts.
     *
     * @param  array{exam_period_id: int, module_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     *
     * @throws ValidationException
     * @throws HardConflictException
     */
    public function execute(Exam $exam, array $data): Exam
    {
        return $this->save->execute($exam, $data);
    }
}
