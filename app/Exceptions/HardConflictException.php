<?php

namespace App\Exceptions;

use App\Services\Scheduling\Conflict;
use App\Services\Scheduling\ConflictResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A write would physically double-book a teacher, room or group (ADR 0002). Non-bypassable: HTTP 422.
 *
 * Any soft conflicts are reported alongside, so the caller sees everything at once.
 */
class HardConflictException extends RuntimeException
{
    public function __construct(public readonly ConflictResult $result)
    {
        parent::__construct(__('messages.conflict_hard', ['count' => count($result->hardConflicts)]));
    }

    /**
     * JSON callers get the structured conflicts; Inertia and form callers get translated
     * messages under `conflicts` (and `soft_conflicts`) in the errors bag.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->result->toArray();

        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'conflicts' => $result['hard_conflicts'],
                'soft_conflicts' => $result['soft_conflicts'],
            ], 422);
        }

        return back()->withInput()->withErrors(array_filter([
            'conflicts' => self::messages($this->result->hardConflicts),
            'soft_conflicts' => self::messages($this->result->softConflicts),
        ]));
    }

    /**
     * @param  list<Conflict>  $conflicts
     * @return list<string>
     */
    public static function messages(array $conflicts): array
    {
        return array_map(fn (Conflict $conflict): string => $conflict->message(), $conflicts);
    }
}
