<?php

namespace App\Actions\Exams;

use App\Exceptions\HardConflictException;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\StudentGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The write path shared by creating and editing an exam: lifecycle and period rules, save and
 * link groups, seat the candidates again in its rooms, and the conflict guard once the exam
 * books its resources, all in one transaction.
 */
class SaveExamAction
{
    public function __construct(
        private GuardExamConflictsAction $guardConflicts,
        private ResplitExamAction $resplit,
        private EnsureExamTimesFitAction $ensureTimesFit,
    ) {}

    /**
     * @param  array{exam_period_id: int, module_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     * @return array{exam: Exam, released: array<int, string>} The exam, and the invigilators its
     *                                                         new time released, by id.
     *
     * @throws ValidationException
     * @throws HardConflictException
     */
    public function execute(Exam $exam, array $data): array
    {
        return DB::transaction(function () use ($exam, $data): array {
            $existed = $exam->exists;

            if ($existed && ! $exam->state->isEditable()) {
                throw ValidationException::withMessages(['exam' => __('messages.exam_locked')]);
            }

            // Shared with period edits, so the period's dates cannot change under this exam.
            $period = ExamPeriod::query()->whereKey($data['exam_period_id'])->lockForUpdate()->firstOrFail();

            $this->ensureTimesFit->execute($period, $data['starts_at']);
            $this->ensureModuleNotExaminedTwice($exam, $data);

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

            // New groups mean new candidates; the rooms must still seat them all.
            if ($existed) {
                $this->resplit->execute($exam);
            }

            $released = $exam->state->occupiesResources() ? $this->guardConflicts->execute($exam) : [];

            return ['exam' => $exam, 'released' => $released];
        });
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
