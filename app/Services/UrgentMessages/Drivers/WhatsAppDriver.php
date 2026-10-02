<?php

namespace App\Services\UrgentMessages\Drivers;

use App\Exceptions\UrgentAlertDeliveryException;
use App\Services\UrgentMessages\UrgentMessageGateway;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Production driver for sending WhatsApp messages via Meta WhatsApp Cloud API.
 */
class WhatsAppDriver implements UrgentMessageGateway
{
    public function __construct(
        protected string $token,
        protected string $phoneNumberId,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     *
     * @throws UrgentAlertDeliveryException
     */
    public function send(string $phone, string $message, array $metadata = []): void
    {
        if ($this->token === '' || $this->phoneNumberId === '') {
            throw new UrgentAlertDeliveryException('WhatsApp credentials are not configured.');
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v20.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => ltrim($phone, '+'),
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if (! $response->successful()) {
                throw new UrgentAlertDeliveryException(
                    "WhatsApp delivery failed: [{$response->status()}] {$response->body()}"
                );
            }
        } catch (UrgentAlertDeliveryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UrgentAlertDeliveryException("WhatsApp HTTP request error: {$e->getMessage()}", 0, $e);
        }
    }
}
