<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class DeleteExamAction
{
    /**
     * Remove a draft or scheduled exam; its group links go with it.
     *
     * @throws ValidationException When the exam is published or later: it is part of the record.
     */
    public function execute(Exam $exam): void
    {
        if (! $exam->state->isEditable()) {
            throw ValidationException::withMessages(['exam' => __('messages.exam_locked')]);
        }

        $exam->delete();
    }
}
