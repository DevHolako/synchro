<?php

namespace App\Exceptions;

use Carbon\CarbonImmutable;

/**
 * A batch of sessions refused, and rolled back, because one of its slots has a conflict
 * that was not overridden (ADR 0002).
 *
 * The slot's own conflict decides the status: 422 for a hard conflict, 409 for an
 * un-overridden soft one. Inertia callers get the messages under `slots`.
 */
class BatchConflictException extends ConflictException
{
    /**
     * @param  int  $slotIndex  The slot's position in the submitted batch.
     * @param  array{starts_at: string, ends_at: string}  $slot
     */
    public function __construct(
        public readonly int $slotIndex,
        public readonly array $slot,
        public readonly ConflictException $conflict,
    ) {
        parent::__construct($conflict->result, __('messages.course_session_batch_conflict', $this->slotParameters()));
    }

    protected function status(): int
    {
        return $this->conflict->status();
    }

    protected function json(): array
    {
        return [
            'slot' => ['index' => $this->slotIndex, ...$this->slot],
            ...$this->result->toArray(),
            'errors' => $this->errors(),
        ];
    }

    protected function errors(): array
    {
        $messages = array_map(
            fn (string $conflict): string => __('messages.course_session_batch_slot_conflict', [
                ...$this->slotParameters(),
                'conflict' => $conflict,
            ]),
            [...$this->result->hardConflictMessages(), ...$this->result->softConflictMessages()],
        );

        return ['slots' => [$this->getMessage(), ...$messages]];
    }

    /**
     * @return array{date: string, start: string, end: string}
     */
    private function slotParameters(): array
    {
        $start = CarbonImmutable::parse($this->slot['starts_at']);
        $end = CarbonImmutable::parse($this->slot['ends_at']);

        return ['date' => $start->toDateString(), 'start' => $start->format('H:i'), 'end' => $end->format('H:i')];
    }
}
