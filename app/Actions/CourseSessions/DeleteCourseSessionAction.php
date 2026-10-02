<?php

namespace App\Actions\CourseSessions;

use App\Models\CourseSession;
use Illuminate\Validation\ValidationException;

class DeleteCourseSessionAction
{
    /**
     * Remove a session that has not started yet; its group links go with it.
     *
     * @throws ValidationException When the session has started: it is part of the record.
     */
    public function execute(CourseSession $session): void
    {
        if ($session->hasStarted()) {
            throw ValidationException::withMessages(['session' => __('messages.course_session_started')]);
        }

        $session->delete();
    }
}
