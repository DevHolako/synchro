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
     * Translated, human-readable descriptions of the hard conflicts.
     *
     * @return list<string>
     */
    public function hardConflictMessages(): array
    {
        return self::messages($this->hardConflicts);
    }

    /**
     * Translated, human-readable descriptions of the soft conflicts.
     *
     * @return list<string>
     */
    public function softConflictMessages(): array
    {
        return self::messages($this->softConflicts);
    }

    /**
     * @param  list<Conflict>  $conflicts
     * @return list<string>
     */
    private static function messages(array $conflicts): array
    {
        return array_map(fn (Conflict $conflict): string => $conflict->message(), $conflicts);
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
