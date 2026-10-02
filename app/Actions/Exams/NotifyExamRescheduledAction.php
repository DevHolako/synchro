<?php

namespace App\Actions\Exams;

use App\Jobs\SendUrgentMessageJob;
use App\Models\Exam;
use App\Models\User;
use App\Notifications\ExamRescheduledNotification;

/**
 * Alerts everyone a rescheduled exam concerns: each candidate (new room and seat) and each
 * invigilator (new room, or that they were released), by queued mail and by urgent message to
 * the phone on their profile (ADR 0003).
 */
class NotifyExamRescheduledAction
{
    /**
     * @param  list<int>  $releasedTeacherIds  Invigilators the new time freed from the exam.
     */
    public function execute(Exam $exam, string $reason, array $releasedTeacherIds): void
    {
        $exam->loadMissing('module:id,code,name');
        $title = "{$exam->module->code} · {$exam->module->name}";
        $when = $exam->starts_at->format('d/m/Y').' · '.$exam->starts_at->format('H:i').'–'.$exam->ends_at->format('H:i');

        $candidates = $exam->candidates()->with(['student.studentProfile:id,user_id,phone', 'roomAssignment.room:id,name'])->get();

        foreach ($candidates as $candidate) {
            $this->alert($candidate->student, $candidate->student->studentProfile?->phone, $title, $when, $reason, __('messages.exam_rescheduled_place', [
                'room' => $candidate->roomAssignment->room->name,
                'seat' => $candidate->seat_number,
            ]));
        }

        $invigilators = $exam->invigilators()->with(['teacher.teacherProfile:id,user_id,phone', 'roomAssignment.room:id,name'])->get();

        foreach ($invigilators as $invigilator) {
            $this->alert($invigilator->teacher, $invigilator->teacher->teacherProfile?->phone, $title, $when, $reason, __('messages.exam_rescheduled_invigilation', [
                'room' => $invigilator->roomAssignment->room->name,
            ]));
        }

        foreach (User::query()->whereKey($releasedTeacherIds)->with('teacherProfile:id,user_id,phone')->get() as $teacher) {
            $this->alert($teacher, $teacher->teacherProfile?->phone, $title, $when, $reason, __('messages.exam_rescheduled_released'));
        }
    }

    private function alert(User $user, ?string $phone, string $title, string $when, string $reason, string $place): void
    {
        $user->notify(new ExamRescheduledNotification($title, $when, $place, $reason));

        if ($phone !== null && $phone !== '') {
            SendUrgentMessageJob::dispatch($phone, __('messages.exam_rescheduled_sms', [
                'exam' => $title,
                'when' => $when,
                'place' => $place,
            ]));
        }
    }
}
