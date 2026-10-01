<?php

namespace App\Services\Scheduling;

use App\Enums\ConflictType;
use Carbon\CarbonInterface;

/**
 * One problem with a candidate slot: a colliding booking (hard) or a policy violation (soft).
 */
final readonly class Conflict
{
    /**
     * @param  int|null  $sessionId  The colliding session, for hard conflicts.
     * @param  CarbonInterface  $startsAt  The colliding booking's window (hard) or the slot's own (soft).
     * @param  array<string, string|int|null>  $details  Facts behind a soft conflict, kept in the override audit.
     */
    public function __construct(
        public ConflictType $type,
        public int $resourceId,
        public string $resourceName,
        public ?int $sessionId,
        public CarbonInterface $startsAt,
        public CarbonInterface $endsAt,
        public array $details = [],
    ) {}

    /**
     * @return array{type: string, resource_id: int, resource_name: string, session_id: int|null, starts_at: string, ends_at: string, details: array<string, string|int|null>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'resource_id' => $this->resourceId,
            'resource_name' => $this->resourceName,
            'session_id' => $this->sessionId,
            'starts_at' => $this->startsAt->format('Y-m-d H:i'),
            'ends_at' => $this->endsAt->format('Y-m-d H:i'),
            'details' => $this->details,
        ];
    }

    /**
     * A translated, human-readable description of the conflict.
     */
    public function message(): string
    {
        return __("messages.conflict_{$this->type->value}", [
            ...$this->details,
            'name' => $this->resourceName,
            'date' => $this->startsAt->format('Y-m-d'),
            'start' => $this->startsAt->format('H:i'),
            'end' => $this->endsAt->format('H:i'),
        ]);
    }
}
