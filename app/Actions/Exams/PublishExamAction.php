<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishExamAction
{
    public function __construct(
        private ChangeExamStateAction $changeState,
        private QueueExamDocumentsAction $queueDocuments,
    ) {}

    /**
     * Make a scheduled exam visible to its candidates once every room has a lead invigilator.
     * From then on its rooms and candidates are locked: only an emergency reschedule changes them.
     * Its convocations and room sheets are generated in the background.
     *
     * @throws ValidationException
     */
    public function execute(Exam $exam, User $publisher): Exam
    {
        return DB::transaction(function () use ($exam, $publisher): Exam {
            // Staffing changes lock the exam too, so no lead can leave between this check and the move.
            Exam::query()->whereKey($exam->id)->lockForUpdate()->first();

            if (Exam::query()->whereKey($exam->id)->missingLead()->exists()) {
                throw ValidationException::withMessages(['exam' => __('messages.exam_lead_missing')]);
            }

            $exam = $this->changeState->execute($exam, ExamState::Published, [
                'published_at' => now(),
                'published_by' => $publisher->id,
            ]);

            $this->queueDocuments->execute([$exam->id]);

            return $exam;
        });
    }
}
