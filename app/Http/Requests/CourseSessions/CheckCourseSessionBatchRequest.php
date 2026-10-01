<?php

namespace App\Http\Requests\CourseSessions;

use App\Models\CourseSession;

/**
 * A would-be batch to check for conflicts without saving it (the wizard's review step).
 */
class CheckCourseSessionBatchRequest extends StoreCourseSessionBatchRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CourseSession::class) ?? false;
    }
}
