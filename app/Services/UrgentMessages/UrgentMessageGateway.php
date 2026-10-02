<?php

namespace App\Services\UrgentMessages;

/**
 * Sends urgent short messages (SMS, WhatsApp) through a configured driver (ADR 0003).
 */
interface UrgentMessageGateway
{
    public function send(string $phone, string $message): void;
}
