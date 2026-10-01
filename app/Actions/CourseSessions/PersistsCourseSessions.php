<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Exceptions\SoftConflictException;
use App\Models\CourseSession;
use App\Services\Scheduling\SessionSlot;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Support\Facades\DB;

/**
 * The shared write path of creating and editing a session: guard, save, link groups, audit overrides.
 *
 * Expects the using action to inject `$guardConflicts` and `$recordOverrides`.
 */
trait PersistsCourseSessions
{
    /**
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
     * @throws SoftConflictException
     */
    private function persist(CourseSession $session, array $data, ?SoftConflictOverride $override): CourseSession
    {
        return DB::transaction(function () use ($session, $data, $override): CourseSession {
            $slot = SessionSlot::fromPayload($data, $session->exists ? $session->id : null);
            $result = $this->guardConflicts->execute($slot, $override);

            $session->fill([
                'module_id' => $data['module_id'],
                'teacher_id' => $data['teacher_id'],
                'room_id' => $data['room_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ])->save();

            $session->studentGroups()->sync($data['student_group_ids']);

            if ($override !== null) {
                $this->recordOverrides->execute($session, $result, $override);
            }

            return $session;
        });
    }
}
