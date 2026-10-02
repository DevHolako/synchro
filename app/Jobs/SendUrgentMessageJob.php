<?php

namespace App\Jobs;

use App\Actions\Exams\SendUrgentMessageAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendUrgentMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Must stay below the `supervisor-default` timeout.
     */
    public int $timeout = 30;

    public function __construct(public string $phone, public string $message)
    {
        $this->onQueue('notifications');
    }

    public function handle(SendUrgentMessageAction $action): void
    {
        $action->execute($this->phone, $this->message);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['urgent-message'];
    }
}
