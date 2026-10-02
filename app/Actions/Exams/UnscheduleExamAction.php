<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class UnscheduleExamAction
{
    public function __construct(private ChangeExamStateAction $changeState) {}

    /**
     * Send a scheduled exam back to Draft, releasing what it booked.
     *
     * @throws ValidationException
     */
    public function execute(Exam $exam): Exam
    {
        return $this->changeState->execute($exam, ExamState::Draft);
    }
}
