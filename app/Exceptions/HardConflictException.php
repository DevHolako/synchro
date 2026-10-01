<?php

namespace App\Exceptions;

use App\Services\Scheduling\ConflictResult;

/**
 * A write would physically double-book a teacher, room or group (ADR 0002). Non-bypassable: HTTP 422.
 *
 * Any soft conflicts are reported alongside, so the caller sees everything at once.
 */
class HardConflictException extends ConflictException
{
    public function __construct(ConflictResult $result)
    {
        parent::__construct($result, __('messages.conflict_hard', ['count' => count($result->hardConflicts)]));
    }

    protected function status(): int
    {
        return 422;
    }

    protected function json(): array
    {
        $result = $this->result->toArray();

        return ['conflicts' => $result['hard_conflicts'], 'soft_conflicts' => $result['soft_conflicts']];
    }

    protected function errors(): array
    {
        return [
            'conflicts' => $this->result->hardConflictMessages(),
            'soft_conflicts' => $this->result->softConflictMessages(),
        ];
    }
}
