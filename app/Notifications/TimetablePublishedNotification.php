<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimetablePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $groupName,
        public string $period,
    ) {
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
        return (new MailMessage)
            ->subject(__('messages.timetable_published_mail_subject'))
            ->greeting(__('messages.mail_greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('messages.timetable_published_mail_line', [
                'group' => $this->groupName,
                'period' => $this->period,
            ]))
            ->action(__('messages.timetable_published_mail_action'), url('/timetable'))
            ->line(__('messages.mail_salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('messages.timetable_published_title'),
            'message' => __('messages.timetable_published_message', [
                'group' => $this->groupName,
                'period' => $this->period,
            ]),
            'type' => 'timetable_published',
            'link' => '/timetable',
        ];
    }
}
