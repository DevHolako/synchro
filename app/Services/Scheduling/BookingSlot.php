<?php

namespace App\Services\Scheduling;

use App\Enums\BookingType;
use Carbon\CarbonImmutable;

/**
 * The resources and time window a booking (course session or exam) would occupy, as checked
 * by the conflict detector.
 *
 * A course session holds one teacher and one room; an exam holds its groups, rooms and
 * invigilators.
 */
final readonly class BookingSlot
{
    /**
     * @param  list<int>  $teacherIds
     * @param  list<int>  $roomIds
     * @param  list<int>  $groupIds
     * @param  int|null  $ignoreId  The booking of this type being edited, which must not conflict with itself.
     */
    public function __construct(
        public BookingType $type,
        public array $teacherIds,
        public array $roomIds,
        public array $groupIds,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public ?int $ignoreId = null,
    ) {}

    /**
     * A course session's slot.
     *
     * @param  array{teacher_id: int, room_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     */
    public static function fromPayload(array $data, ?int $ignoreSessionId = null): self
    {
        return new self(
            type: BookingType::CourseSession,
            teacherIds: [$data['teacher_id']],
            roomIds: [$data['room_id']],
            groupIds: $data['student_group_ids'],
            startsAt: CarbonImmutable::parse($data['starts_at']),
            endsAt: CarbonImmutable::parse($data['ends_at']),
            ignoreId: $ignoreSessionId,
        );
    }

    /**
     * An exam's slot: its groups for now (rooms and invigilators join with room allocation).
     *
     * @param  array{student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     */
    public static function forExam(array $data, ?int $ignoreExamId = null): self
    {
        return new self(
            type: BookingType::Exam,
            teacherIds: [],
            roomIds: [],
            groupIds: $data['student_group_ids'],
            startsAt: CarbonImmutable::parse($data['starts_at']),
            endsAt: CarbonImmutable::parse($data['ends_at']),
            ignoreId: $ignoreExamId,
        );
    }

    /**
     * The id of the booking of this kind to leave out of the check, if any.
     */
    public function ignoredIdOf(BookingType $type): ?int
    {
        return $this->type === $type ? $this->ignoreId : null;
    }
}
