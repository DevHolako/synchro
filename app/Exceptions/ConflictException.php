<?php

namespace App\Exceptions;

use App\Services\Scheduling\ConflictResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * A scheduling write refused because of conflicts (ADR 0002).
 *
 * JSON callers get the structured conflicts with the subclass's status code; Inertia and
 * form callers are sent back with translated messages in the errors bag.
 */
abstract class ConflictException extends RuntimeException
{
    public function __construct(public readonly ConflictResult $result, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    abstract protected function status(): int;

    /**
     * @return array<string, mixed>
     */
    abstract protected function json(): array;

    /**
     * @return array<string, list<string>>
     */
    abstract protected function errors(): array;

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return new JsonResponse(['message' => $this->getMessage(), ...$this->json()], $this->status());
        }

        return back()->withInput()->withErrors(array_filter($this->errors()));
    }
}
