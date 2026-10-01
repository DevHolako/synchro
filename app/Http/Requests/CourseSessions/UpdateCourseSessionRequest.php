<?php

namespace App\Http\Requests\CourseSessions;

use App\Models\CourseSession;

class UpdateCourseSessionRequest extends StoreCourseSessionRequest
{
    public function authorize(): bool
    {
        /** @var CourseSession $session */
        $session = $this->route('session');

        return ($this->user()?->can('update', $session) ?? false) && $this->mayOverride();
    }
}
