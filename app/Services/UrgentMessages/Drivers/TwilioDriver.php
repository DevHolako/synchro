<?php

namespace App\Services\UrgentMessages\Drivers;

use App\Exceptions\UrgentAlertDeliveryException;
use App\Services\UrgentMessages\UrgentMessageGateway;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Production driver for sending SMS via Twilio Messages API.
 */
class TwilioDriver implements UrgentMessageGateway
{
    public function __construct(
        protected string $accountSid,
        protected string $authToken,
        protected string $from,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     *
     * @throws UrgentAlertDeliveryException
     */
    public function send(string $phone, string $message, array $metadata = []): void
    {
        if ($this->accountSid === '' || $this->authToken === '' || $this->from === '') {
            throw new UrgentAlertDeliveryException('Twilio credentials are not configured.');
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'To' => $phone,
                    'From' => $this->from,
                    'Body' => $message,
                ]);

            if (! $response->successful()) {
                throw new UrgentAlertDeliveryException(
                    "Twilio SMS delivery failed: [{$response->status()}] {$response->body()}"
                );
            }
        } catch (UrgentAlertDeliveryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UrgentAlertDeliveryException("Twilio HTTP request error: {$e->getMessage()}", 0, $e);
        }
    }
}
