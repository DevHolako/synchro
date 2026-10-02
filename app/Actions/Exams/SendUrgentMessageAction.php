<?php

namespace App\Actions\Exams;

use App\Services\UrgentMessages\UrgentMessageGateway;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Sends one urgent message once: a key claimed before sending makes a redelivered or retried job
 * a no-op once the message went out; a failed send frees the key for the retry.
 */
class SendUrgentMessageAction
{
    /** How long a sent message's key is remembered. */
    private const int REMEMBER_DAYS = 7;

    public function __construct(private UrgentMessageGateway $gateway) {}

    /**
     * @return bool Whether the message was sent now (false when it already went out).
     *
     * @throws Throwable When the gateway fails; the job retries.
     */
    public function execute(string $key, string $phone, string $message): bool
    {
        $cacheKey = "urgent-message:{$key}";

        if (! Cache::add($cacheKey, true, now()->addDays(self::REMEMBER_DAYS))) {
            return false;
        }

        try {
            $this->gateway->send($phone, $message);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            throw $exception;
        }

        return true;
    }
}
