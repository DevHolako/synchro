<?php

namespace App\Services\UrgentMessages\Drivers;

use App\Models\UrgentAlert;
use App\Services\UrgentMessages\UrgentMessageGateway;

/**
 * Database driver persisting alerts to urgent_alerts table for test assertions and inspection.
 */
class DatabaseDriver implements UrgentMessageGateway
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(string $phone, string $message, array $metadata = []): void
    {
        UrgentAlert::create([
            'phone' => $phone,
            'message' => $message,
            'driver' => 'database',
            'status' => 'sent',
            'metadata' => $metadata ?: null,
        ]);
    }
}
