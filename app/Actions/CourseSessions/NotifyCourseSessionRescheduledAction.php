<?php

namespace App\Actions\CourseSessions;

use App\Jobs\SendUrgentMessageJob;
use App\Models\CourseSession;
use App\Models\StudentProfile;

final class NotifyCourseSessionRescheduledAction
{
    public function execute(CourseSession $session, string $originalTime): void
    {
        $session->loadMissing(['module', 'teacher.teacherProfile', 'studentGroups', 'room']);

        $moduleName = $session->module?->name ?? 'Séance';
        $when = $session->starts_at->format('d/m/Y H:i').' - '.$session->ends_at->format('H:i');
        $roomName = $session->room?->name ?? 'Salle';
        $message = __('messages.course_session_rescheduled_sms', [
            'session' => $moduleName,
            'when' => $when,
            'room' => $roomName,
        ]);

        $keyPrefix = "session-{$session->id}-reschedule-{$session->updated_at?->timestamp}";

        // Notify teacher
        if ($session->teacher !== null) {
            $phone = $session->teacher->teacherProfile?->phone;
            if ($phone !== null && $phone !== '') {
                SendUrgentMessageJob::dispatch(
                    "{$keyPrefix}-teacher-{$session->teacher->id}",
                    $phone,
                    $message,
                    ['session_id' => $session->id, 'type' => 'course_session_reschedule'],
                );
            }
        }

        // Notify students in assigned groups
        $groupIds = $session->studentGroups->pluck('id')->all();
        if (! empty($groupIds)) {
            $studentProfiles = StudentProfile::query()
                ->whereIn('student_group_id', $groupIds)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get();

            foreach ($studentProfiles as $profile) {
                SendUrgentMessageJob::dispatch(
                    "{$keyPrefix}-student-{$profile->user_id}",
                    $profile->phone,
                    $message,
                    ['session_id' => $session->id, 'type' => 'course_session_reschedule'],
                );
            }
        }
    }
}
