<?php

namespace App\Jobs;

use App\Actions\Exams\SendUrgentMessageAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendUrgentMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Must stay below the `supervisor-default` timeout.
     */
    public int $timeout = 30;

    /**
     * @param  string  $key  Identifies this message, so a redelivery does not send it twice.
     */
    public function __construct(public string $key, public string $phone, public string $message)
    {
        $this->onQueue('notifications');
    }

    public function handle(SendUrgentMessageAction $action): void
    {
        $action->execute($this->key, $this->phone, $this->message);
    }

    /**
     * Record that the alert never went out, for follow-up by phone.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Urgent message not sent', ['key' => $this->key, 'error' => $exception?->getMessage()]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['urgent-message', $this->key];
    }
}
