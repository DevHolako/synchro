<?php

namespace App\Actions\Exams;

use App\Actions\CourseSessions\GuardSessionConflictsAction;
use App\Enums\ExamState;
use App\Exceptions\HardConflictException;
use App\Models\Exam;
use App\Services\Scheduling\SessionSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleExamAction
{
    public function __construct(
        private GuardSessionConflictsAction $guardConflicts,
        private ChangeExamStateAction $changeState,
    ) {}

    /**
     * Move a draft to Scheduled: from then on it books its groups, so it must not clash with
     * any course session or other booked exam.
     *
     * @throws ValidationException
     * @throws HardConflictException
     */
    public function execute(Exam $exam): Exam
    {
        $this->changeState->ensureAllowed($exam, ExamState::Scheduled);

        return DB::transaction(function () use ($exam): Exam {
            $this->guardConflicts->execute(SessionSlot::forExam([
                'student_group_ids' => array_values(array_map('intval', $exam->studentGroups()->pluck('student_groups.id')->all())),
                'starts_at' => $exam->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $exam->ends_at->format('Y-m-d H:i:s'),
            ], $exam->id));

            return $this->changeState->execute($exam, ExamState::Scheduled);
        });
    }
}
