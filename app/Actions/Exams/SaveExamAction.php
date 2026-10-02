<?php

namespace App\Actions\Exams;

use App\Actions\CourseSessions\GuardSessionConflictsAction;
use App\Exceptions\HardConflictException;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\StudentGroup;
use App\Services\Scheduling\SessionSlot;
use App\Support\SchoolClock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The write path shared by creating and editing an exam: lifecycle and period rules, the
 * conflict guard once the exam books its groups, then save and link groups, in one transaction.
 */
class SaveExamAction
{
    public function __construct(private GuardSessionConflictsAction $guardConflicts) {}

    /**
     * @param  array{exam_period_id: int, module_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     *
     * @throws ValidationException
     * @throws HardConflictException
     */
    public function execute(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data): Exam {
            $existed = $exam->exists;

            if ($existed && ! $exam->state->isEditable()) {
                throw ValidationException::withMessages(['exam' => __('messages.exam_locked')]);
            }

            // Shared with period edits, so the period's dates cannot change under this exam.
            $period = ExamPeriod::query()->whereKey($data['exam_period_id'])->lockForUpdate()->firstOrFail();

            $this->ensureTimesFit($period, $data);
            $this->ensureModuleNotExaminedTwice($exam, $data);

            if ($exam->state->occupiesResources()) {
                $this->guardConflicts->execute(SessionSlot::forExam($data, $existed ? $exam->id : null));
            }

            $exam->fill([
                'exam_period_id' => $data['exam_period_id'],
                'module_id' => $data['module_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ])->save();

            $changes = $exam->studentGroups()->sync($data['student_group_ids']);

            // A change to the groups alone is still a change to the exam (iCal SEQUENCE, LAST-MODIFIED).
            if ($existed && ! $exam->wasChanged() && array_filter($changes) !== []) {
                $exam->touch();
            }

            return $exam;
        });
    }

    /**
     * Inside the period's dates, and not already begun by the school's clock.
     *
     * @param  array{starts_at: string}  $data
     */
    private function ensureTimesFit(ExamPeriod $period, array $data): void
    {
        $start = CarbonImmutable::parse($data['starts_at']);
        $day = $start->format('Y-m-d');

        if ($day < $period->start_date->format('Y-m-d') || $day > $period->end_date->format('Y-m-d')) {
            throw ValidationException::withMessages(['starts_at' => __('messages.exam_outside_period', [
                'start' => $period->start_date->format('Y-m-d'),
                'end' => $period->end_date->format('Y-m-d'),
            ])]);
        }

        if ($start->lessThanOrEqualTo(SchoolClock::now())) {
            throw ValidationException::withMessages(['starts_at' => __('messages.exam_in_past')]);
        }
    }

    /**
     * A group sits each module's exam at most once per period.
     *
     * @param  array{exam_period_id: int, module_id: int, student_group_ids: list<int>}  $data
     */
    private function ensureModuleNotExaminedTwice(Exam $exam, array $data): void
    {
        $group = StudentGroup::query()
            ->whereKey($data['student_group_ids'])
            ->whereHas('exams', fn ($exams) => $exams
                ->where('exams.exam_period_id', $data['exam_period_id'])
                ->where('exams.module_id', $data['module_id'])
                ->when($exam->exists, fn ($query) => $query->where('exams.id', '!=', $exam->id)))
            ->orderBy('name')
            ->first(['id', 'name']);

        if ($group !== null) {
            throw ValidationException::withMessages(['student_group_ids' => __('messages.exam_duplicate_module', ['group' => $group->name])]);
        }
    }
}
