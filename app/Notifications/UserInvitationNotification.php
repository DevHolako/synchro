<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $activationUrl,
        public readonly CarbonInterface $expiresAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.invitation_mail_subject', ['app' => config('app.name')]))
            ->greeting(__('messages.invitation_mail_greeting', ['name' => $notifiable->name]))
            ->line(__('messages.invitation_mail_intro', ['app' => config('app.name')]))
            ->action(__('messages.invitation_mail_action'), $this->activationUrl)
            ->line(__('messages.invitation_mail_expiry', [
                'hours' => (int) round(now()->diffInHours($this->expiresAt)),
            ]))
            ->line(__('messages.invitation_mail_ignore'));
    }
}
