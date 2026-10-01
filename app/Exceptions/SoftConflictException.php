<?php

namespace App\Exceptions;

use App\Services\Scheduling\ConflictResult;

/**
 * A write breaks a scheduling policy (ADR 0002) and no override was given. HTTP 409: the caller
 * may confirm by resubmitting with `force_override` and a `justification`.
 */
class SoftConflictException extends ConflictException
{
    public function __construct(ConflictResult $result)
    {
        parent::__construct($result, __('messages.conflict_soft', ['count' => count($result->softConflicts)]));
    }

    protected function status(): int
    {
        return 409;
    }

    protected function json(): array
    {
        return ['soft_conflicts' => $this->result->toArray()['soft_conflicts']];
    }

    protected function errors(): array
    {
        return ['soft_conflicts' => $this->result->softConflictMessages()];
    }
}
