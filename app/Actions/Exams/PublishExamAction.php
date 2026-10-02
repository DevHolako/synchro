<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PublishExamAction
{
    public function __construct(private ChangeExamStateAction $changeState) {}

    /**
     * Make a scheduled exam visible to its candidates. From then on it is locked: only an
     * emergency reschedule changes it.
     *
     * @throws ValidationException
     */
    public function execute(Exam $exam, User $publisher): Exam
    {
        return $this->changeState->execute($exam, ExamState::Published, [
            'published_at' => now(),
            'published_by' => $publisher->id,
        ]);
    }
}
