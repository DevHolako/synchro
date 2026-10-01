<?php

namespace App\Http\Requests\CourseSessions;

/**
 * A would-be session to check for conflicts without saving it (drag-and-drop preview).
 */
class CheckCourseSessionRequest extends StoreCourseSessionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'ignore_session_id' => ['nullable', 'integer', 'exists:course_sessions,id'],
        ];
    }

    public function ignoreSessionId(): ?int
    {
        return $this->nullableInteger('ignore_session_id');
    }
}
