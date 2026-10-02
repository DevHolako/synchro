<?php

namespace App\Actions\Exams;

use App\Enums\ExamState;
use App\Models\Exam;
use Illuminate\Validation\ValidationException;

/**
 * Moves one exam along the lifecycle (ADR 0005), refusing moves the lifecycle forbids.
 *
 * The move is a conditional update on the state the exam was read in, so a concurrent or
 * repeated move cannot apply twice.
 */
class ChangeExamStateAction
{
    /**
     * @param  array<string, mixed>  $attributes  Saved with the move (e.g. who published).
     *
     * @throws ValidationException
     */
    public function execute(Exam $exam, ExamState $target, array $attributes = []): Exam
    {
        $this->ensureAllowed($exam, $target);

        $moved = Exam::query()
            ->whereKey($exam->id)
            ->where('state', $exam->state)
            ->update([...$attributes, 'state' => $target]);

        if ($moved === 0) {
            throw $this->notAllowed($exam->state, $target);
        }

        return $exam->refresh();
    }

    /**
     * The lifecycle allows the move, and an exam is only scheduled or published before it starts.
     *
     * @throws ValidationException
     */
    public function ensureAllowed(Exam $exam, ExamState $target): void
    {
        if (! $exam->state->canTransitionTo($target)) {
            throw $this->notAllowed($exam->state, $target);
        }

        if (($target === ExamState::Scheduled || $target === ExamState::Published) && $exam->hasStarted()) {
            throw ValidationException::withMessages(['exam' => __('messages.exam_in_past')]);
        }
    }

    private function notAllowed(ExamState $from, ExamState $to): ValidationException
    {
        return ValidationException::withMessages(['exam' => __('messages.exam_transition_not_allowed', [
            'from' => __("messages.exam_state_{$from->value}"),
            'to' => __("messages.exam_state_{$to->value}"),
        ])]);
    }
}
