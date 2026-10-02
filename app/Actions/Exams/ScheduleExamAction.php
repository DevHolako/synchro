<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Exceptions\HardConflictException;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleExamAction
{
    public function __construct(
        private GuardExamConflictsAction $guardConflicts,
        private ResplitExamAction $resplit,
        private ChangeExamStateAction $changeState,
    ) {}

    /**
     * Move a draft to Scheduled: it needs rooms and candidates, its split is refreshed, and from
     * then on it books its rooms, invigilators and groups, so none may be taken by a course
     * session or another booked exam.
     *
     * @throws ValidationException
     * @throws HardConflictException
     */
    public function execute(Exam $exam): Exam
    {
        $this->changeState->ensureAllowed($exam, ExamState::Scheduled);

        return DB::transaction(function () use ($exam): Exam {
            if ($exam->roomAssignments()->doesntExist()) {
                throw ValidationException::withMessages(['exam' => __('messages.exam_rooms_missing')]);
            }

            // Students may have joined or left the groups since the rooms were chosen.
            if ($this->resplit->execute($exam) === 0) {
                throw ValidationException::withMessages(['exam' => __('messages.exam_no_candidates')]);
            }

            $this->guardConflicts->execute($exam);

            return $this->changeState->execute($exam, ExamState::Scheduled);
        });
    }
}
