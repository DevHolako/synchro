<?php

namespace App\Notifications;

use App\Models\ExamCandidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamConvocationPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ExamCandidate $candidate)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->candidate->exam;
        $roomName = $this->candidate->roomAssignment?->room?->name ?? '—';
        $startsAt = $exam->starts_at ? $exam->starts_at->format('d/m/Y H:i') : '—';

        return (new MailMessage)
            ->subject(__('messages.convocation_published_mail_subject', ['module' => $exam->module->name]))
            ->greeting(__('messages.mail_greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('messages.convocation_published_mail_line', [
                'module' => $exam->module->name,
                'starts_at' => $startsAt,
                'room' => $roomName,
                'seat' => $this->candidate->seat_number,
            ]))
            ->action(__('messages.convocation_published_mail_action'), url("/exams/{$exam->id}/convocation"))
            ->line(__('messages.mail_salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $exam = $this->candidate->exam;
        $startsAt = $exam->starts_at ? $exam->starts_at->format('d/m/Y H:i') : '—';

        return [
            'title' => __('messages.convocation_published_title'),
            'message' => __('messages.convocation_published_message', [
                'module' => $exam->module->name,
                'starts_at' => $startsAt,
            ]),
            'type' => 'exam_convocation_published',
            'link' => "/exams/{$exam->id}/convocation",
            'exam_id' => $exam->id,
            'convocation_uuid' => $this->candidate->convocation_uuid,
        ];
    }
}
