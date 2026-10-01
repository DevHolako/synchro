<?php

namespace App\Services\Scheduling;

/**
 * The outcome of a conflict check: non-bypassable hard conflicts and overrideable soft ones (ADR 0002).
 */
final readonly class ConflictResult
{
    /**
     * @param  list<Conflict>  $hardConflicts
     * @param  list<Conflict>  $softConflicts
     */
    public function __construct(
        public array $hardConflicts = [],
        public array $softConflicts = [],
    ) {}

    public function hasHardConflicts(): bool
    {
        return $this->hardConflicts !== [];
    }

    public function hasSoftConflicts(): bool
    {
        return $this->softConflicts !== [];
    }

    /**
     * @return array{has_hard_conflicts: bool, hard_conflicts: list<array<string, mixed>>, has_soft_conflicts: bool, soft_conflicts: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'has_hard_conflicts' => $this->hasHardConflicts(),
            'hard_conflicts' => array_map(fn (Conflict $conflict): array => $conflict->toArray(), $this->hardConflicts),
            'has_soft_conflicts' => $this->hasSoftConflicts(),
            'soft_conflicts' => array_map(fn (Conflict $conflict): array => $conflict->toArray(), $this->softConflicts),
        ];
    }
}
