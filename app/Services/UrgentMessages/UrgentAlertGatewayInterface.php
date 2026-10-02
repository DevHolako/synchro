<?php

namespace App\Services\UrgentMessages;

/**
 * Interface contract matching Spec 06 Ticket 01 terminology.
 */
interface UrgentAlertGatewayInterface extends UrgentMessageGateway
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function sendUrgentAlert(string $recipientPhone, string $message, array $metadata = []): bool;
}
