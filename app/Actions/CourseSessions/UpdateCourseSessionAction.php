<?php

namespace App\Actions\CourseSessions;

use App\Exceptions\HardConflictException;
use App\Models\CourseSession;
use Illuminate\Support\Facades\DB;

class UpdateCourseSessionAction
{
    public function __construct(private GuardSessionConflictsAction $guardConflicts) {}

    /**
     * Move or re-staff a session, refusing any hard conflict with other sessions.
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
    public function execute(CourseSession $session, array $data): CourseSession
    {
        return DB::transaction(function () use ($session, $data): CourseSession {
            $this->guardConflicts->execute(CreateCourseSessionAction::slot($data, $session->id));

            $session->update([
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
}
