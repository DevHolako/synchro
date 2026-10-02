<?php

namespace App\Services\UrgentMessages;

use Illuminate\Support\Facades\Log;

/**
 * The development driver: urgent messages are written to the log, nothing is sent.
 * SMS and WhatsApp drivers arrive with the notification gateway (Part 06).
 */
class LogUrgentMessageGateway implements UrgentMessageGateway
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(string $phone, string $message, array $metadata = []): void
    {
        $context = ['phone' => $phone, 'message' => $message];
        if (! empty($metadata)) {
            $context['metadata'] = $metadata;
        }

        Log::info('Urgent message', $context);
    }
}
