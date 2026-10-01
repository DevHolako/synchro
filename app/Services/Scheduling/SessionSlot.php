<?php

namespace App\Services\Scheduling;

use Carbon\CarbonImmutable;

/**
 * The resources and time window a session would occupy, as checked by the conflict detector.
 */
final readonly class SessionSlot
{
    /**
     * @param  list<int>  $groupIds
     * @param  int|null  $ignoreSessionId  The session being edited, which must not conflict with itself.
     */
    public function __construct(
        public int $teacherId,
        public int $roomId,
        public array $groupIds,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public ?int $ignoreSessionId = null,
    ) {}
}
