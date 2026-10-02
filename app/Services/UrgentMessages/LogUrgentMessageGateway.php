<?php

namespace App\Services\UrgentMessages;

use Illuminate\Support\Facades\Log;

/**
 * The development driver: urgent messages are written to the log, nothing is sent.
 * SMS and WhatsApp drivers arrive with the notification gateway (Part 06).
 */
class LogUrgentMessageGateway implements UrgentMessageGateway
{
    public function send(string $phone, string $message): void
    {
        Log::info('Urgent message', ['phone' => $phone, 'message' => $message]);
    }
}
