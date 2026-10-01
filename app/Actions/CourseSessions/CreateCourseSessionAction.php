<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\SessionSlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateCourseSessionAction
{
    public function __construct(private GuardSessionConflictsAction $guardConflicts) {}

    /**
     * Schedule a session, refusing any hard conflict.
     *
     * @param array{
     *     module_id: int,
     *     teacher_id: int,
     *     room_id: int,
     *     student_group_ids: list<int>,
     *     starts_at: string,
     *     ends_at: string
     * } $data
     *
     * @throws HardConflictException
     */
    public function execute(array $data): CourseSession
    {
        return DB::transaction(function () use ($data): CourseSession {
            $this->guardConflicts->execute(self::slot($data));

            $session = CourseSession::create([
                'module_id' => $data['module_id'],
                'teacher_id' => $data['teacher_id'],
                'room_id' => $data['room_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ]);

            $session->studentGroups()->sync($data['student_group_ids']);

            return $session;
        });
    }

    /**
     * @param  array{teacher_id: int, room_id: int, student_group_ids: list<int>, starts_at: string, ends_at: string}  $data
     */
    public static function slot(array $data, ?int $ignoreSessionId = null): SessionSlot
    {
        return new SessionSlot(
            teacherId: $data['teacher_id'],
            roomId: $data['room_id'],
            groupIds: $data['student_group_ids'],
            startsAt: CarbonImmutable::parse($data['starts_at']),
            endsAt: CarbonImmutable::parse($data['ends_at']),
            ignoreSessionId: $ignoreSessionId,
        );
    }
}
