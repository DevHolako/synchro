<?php

namespace App\Services\UrgentMessages;

/**
 * Sends urgent short messages (SMS, WhatsApp) through a configured driver (ADR 0003).
 */
interface UrgentMessageGateway
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(string $phone, string $message, array $metadata = []): void;
}
