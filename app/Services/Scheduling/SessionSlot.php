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

    /**
     * @param  array{teacher_id: int, room_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     */
    public static function fromPayload(array $data, ?int $ignoreSessionId = null): self
    {
        return new self(
            teacherId: $data['teacher_id'],
            roomId: $data['room_id'],
            groupIds: $data['student_group_ids'],
            startsAt: CarbonImmutable::parse($data['starts_at']),
            endsAt: CarbonImmutable::parse($data['ends_at']),
            ignoreSessionId: $ignoreSessionId,
        );
    }
}
