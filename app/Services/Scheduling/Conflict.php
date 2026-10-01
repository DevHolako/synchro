<?php

namespace App\Services\Scheduling;

use App\Enums\ConflictType;
use Carbon\CarbonInterface;

/**
 * One booking that collides with a candidate slot.
 */
final readonly class Conflict
{
    public function __construct(
        public ConflictType $type,
        public int $resourceId,
        public string $resourceName,
        public int $sessionId,
        public CarbonInterface $startsAt,
        public CarbonInterface $endsAt,
    ) {}

    /**
     * @return array{type: string, resource_id: int, resource_name: string, session_id: int, starts_at: string, ends_at: string}
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
        ];
    }

    /**
     * A translated, human-readable description of the conflict.
     */
    public function message(): string
    {
        return __("messages.conflict_{$this->type->value}", [
            'name' => $this->resourceName,
            'date' => $this->startsAt->format('Y-m-d'),
            'start' => $this->startsAt->format('H:i'),
            'end' => $this->endsAt->format('H:i'),
        ]);
    }
}
