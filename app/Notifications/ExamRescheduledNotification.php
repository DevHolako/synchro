<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Data\RescheduledExam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An urgent mail: a published exam moved (emergency reschedule), with where to go now.
 */
class ExamRescheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $place  The recipient's new room (and seat), or why they are no longer expected.
     */
    public function __construct(
        public readonly RescheduledExam $exam,
        public readonly string $place,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return ['mail' => 'notifications'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.exam_rescheduled_mail_subject', ['exam' => $this->exam->title]))
            ->greeting(__('messages.exam_rescheduled_mail_greeting', ['name' => $notifiable->name]))
            ->line(__('messages.exam_rescheduled_mail_intro', ['exam' => $this->exam->title]))
            ->line(__('messages.exam_rescheduled_mail_when', ['when' => $this->exam->when]))
            ->line($this->place)
            ->line(__('messages.exam_rescheduled_mail_reason', ['reason' => $this->exam->reason]))
            ->action(__('messages.exam_rescheduled_mail_action'), route('exams.index'))
            ->line(__('messages.exam_rescheduled_mail_old_convocation'));
    }
}
