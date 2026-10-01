<?php

namespace App\Actions\CourseSessions;

use App\Models\CourseSession;

class DeleteCourseSessionAction
{
    /**
     * Remove a session; its group links go with it.
     */
    public function execute(CourseSession $session): void
    {
        $session->delete();
    }
}
