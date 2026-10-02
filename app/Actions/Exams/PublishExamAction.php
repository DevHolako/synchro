<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Enums\InvigilatorRole;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PublishExamAction
{
    public function __construct(private ChangeExamStateAction $changeState) {}

    /**
     * Make a scheduled exam visible to its candidates once every room has a lead invigilator.
     * From then on its rooms and candidates are locked: only an emergency reschedule changes them.
     *
     * @throws ValidationException
     */
    public function execute(Exam $exam, User $publisher): Exam
    {
        if ($exam->roomAssignments()->whereDoesntHave('invigilators', fn ($invigilators) => $invigilators->where('role', InvigilatorRole::Principal))->exists()) {
            throw ValidationException::withMessages(['exam' => __('messages.exam_lead_missing')]);
        }

        return $this->changeState->execute($exam, ExamState::Published, [
            'published_at' => now(),
            'published_by' => $publisher->id,
        ]);
    }
}
