<?php

namespace App\Actions\Exams;

use App\Jobs\SendUrgentMessageJob;
use App\Models\Exam;
use App\Models\User;
use App\Notifications\Data\RescheduledExam;
use App\Notifications\ExamRescheduledNotification;

/**
 * Alerts everyone a rescheduled exam concerns: each candidate (new room and seat) and each
 * invigilator (new room, or that they were released), by queued mail and by urgent message to
 * the phone on their profile (ADR 0003).
 */
class NotifyExamRescheduledAction
{
    /**
     * @param  list<int>  $busyTeacherIds  Invigilators released because they are busy or unavailable at the new time.
     * @param  list<int>  $droppedTeacherIds  Invigilators released because their room is no longer used.
     */
    public function execute(Exam $exam, string $reason, array $busyTeacherIds, array $droppedTeacherIds = []): void
    {
        $exam->loadMissing('module:id,code,name');
        $details = new RescheduledExam(
            title: $exam->module->label(),
            when: __('messages.exam_rescheduled_when', [
                'date' => $exam->starts_at->settings(['locale' => app()->getLocale()])->isoFormat('LL'),
                'start' => $exam->starts_at->format('H:i'),
                'end' => $exam->ends_at->format('H:i'),
            ]),
            reason: $reason,
            key: "exam-{$exam->id}-revision-{$exam->revision}",
        );

        $candidates = $exam->candidates()->with(['student.studentProfile:id,user_id,phone', 'roomAssignment.room:id,name'])->get();

        foreach ($candidates as $candidate) {
            $this->alert($candidate->student, $candidate->student->studentProfile?->phone, $details, __('messages.exam_rescheduled_place', [
                'room' => $candidate->roomAssignment->room->name,
                'seat' => $candidate->seat_number,
            ]));
        }

        $invigilators = $exam->invigilators()->with(['teacher.teacherProfile:id,user_id,phone', 'roomAssignment.room:id,name'])->get();

        foreach ($invigilators as $invigilator) {
            $this->alert($invigilator->teacher, $invigilator->teacher->teacherProfile?->phone, $details, __('messages.exam_rescheduled_invigilation', [
                'room' => $invigilator->roomAssignment->room->name,
            ]));
        }

        foreach (User::query()->whereKey([...$busyTeacherIds, ...$droppedTeacherIds])->with('teacherProfile:id,user_id,phone')->get() as $teacher) {
            $this->alert($teacher, $teacher->teacherProfile?->phone, $details, in_array($teacher->id, $droppedTeacherIds, true)
                ? __('messages.exam_rescheduled_room_dropped')
                : __('messages.exam_rescheduled_released'));
        }
    }

    private function alert(User $user, ?string $phone, RescheduledExam $details, string $place): void
    {
        $user->notify(new ExamRescheduledNotification($details, $place));

        if ($phone !== null && $phone !== '') {
            SendUrgentMessageJob::dispatch("{$details->key}-user-{$user->id}", $phone, __('messages.exam_rescheduled_sms', [
                'exam' => $details->title,
                'when' => $details->when,
                'place' => $place,
            ]));
        }
    }
}
