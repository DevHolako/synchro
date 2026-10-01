<?php

namespace App\Exceptions;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A batch of sessions refused, and rolled back, because one of its slots has a conflict
 * that was not overridden (ADR 0002).
 *
 * The slot's own conflict decides the status: 422 for a hard conflict, 409 for an
 * un-overridden soft one. Inertia callers get the messages under `slots`.
 */
class BatchConflictException extends RuntimeException
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
        parent::__construct(__('messages.course_session_batch_conflict', $this->slotParameters()), previous: $conflict);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->conflict->result;

        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'slot' => ['index' => $this->slotIndex, ...$this->slot],
                ...$result->toArray(),
            ], $this->conflict instanceof HardConflictException ? 422 : 409);
        }

        $messages = array_map(
            fn (string $conflict): string => __('messages.course_session_batch_slot_conflict', [
                ...$this->slotParameters(),
                'conflict' => $conflict,
            ]),
            [...$result->hardConflictMessages(), ...$result->softConflictMessages()],
        );

        return back()->withInput()->withErrors(['slots' => [$this->getMessage(), ...$messages]]);
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
