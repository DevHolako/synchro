<?php

namespace App\Exceptions;

use App\Services\Scheduling\ConflictResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A write breaks a scheduling policy (ADR 0002) and no override was given. HTTP 409: the caller
 * may confirm by resubmitting with `force_override` and a `justification`.
 */
class SoftConflictException extends RuntimeException
{
    public function __construct(public readonly ConflictResult $result)
    {
        parent::__construct(__('messages.conflict_soft', ['count' => count($result->softConflicts)]));
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'soft_conflicts' => $this->result->toArray()['soft_conflicts'],
            ], 409);
        }

        return back()->withInput()->withErrors([
            'soft_conflicts' => HardConflictException::messages($this->result->softConflicts),
        ]);
    }
}
