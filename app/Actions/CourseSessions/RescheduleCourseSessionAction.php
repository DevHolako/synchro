<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\SoftConflictOverride;
use App\Support\SchoolClock;

/**
 * Moves or resizes a session in time, keeping its module, teacher, room and groups.
 *
 * The resources are not re-validated as active, so a session of a since-deactivated room
 * can still move; conflicts are checked afresh like any write.
 */
class RescheduleCourseSessionAction
{
    public function __construct(
        private SaveCourseSessionAction $save,
        private ?NotifyCourseSessionRescheduledAction $notifyAction = null,
    ) {
        $this->notifyAction ??= app(NotifyCourseSessionRescheduledAction::class);
    }

    /**
     * @param  array{starts_at: string, ends_at: string}  $times
     *
     * @throws HardConflictException
     * @throws SoftConflictException
     */
    public function execute(CourseSession $session, array $times, ?SoftConflictOverride $override = null): CourseSession
    {
        $originalStart = $session->starts_at?->copy();
        $now = SchoolClock::now();

        $savedSession = $this->save->execute($session, [
            'module_id' => $session->module_id,
            'teacher_id' => $session->teacher_id,
            'room_id' => $session->room_id,
            'student_group_ids' => array_values(array_map('intval', $session->studentGroups()->pluck('student_groups.id')->all())),
            'starts_at' => $times['starts_at'],
            'ends_at' => $times['ends_at'],
        ], $override);

        if ($originalStart !== null && $originalStart->greaterThanOrEqualTo($now) && $now->diffInMinutes($originalStart) <= 120) {
            $this->notifyAction->execute($savedSession, $originalStart->toIso8601String());
        }

        return $savedSession;
    }
}
