<?php

namespace App\Actions\Exams;

use App\Services\UrgentMessages\UrgentMessageGateway;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/**
 * Sends one urgent message once, however often its job is delivered.
 *
 * A short claim covers the send: a duplicate delivery meanwhile backs off and retries, and a
 * worker killed mid-send leaves a claim that expires, so the retry still sends. Only a
 * successful send is remembered for good, which turns every later delivery into a no-op.
 */
class SendUrgentMessageAction
{
    /** How long a sent message is remembered. */
    private const int REMEMBER_DAYS = 7;

    /** How long a send may hold its claim: longer than the job's timeout. */
    private const int CLAIM_SECONDS = 120;

    public function __construct(private UrgentMessageGateway $gateway) {}

    /**
     * @return bool Whether the message was sent now (false when it already went out).
     *
     * @throws RuntimeException When another delivery is sending it right now; the job retries.
     * @throws Throwable When the gateway fails; the job retries.
     */
    public function execute(string $key, string $phone, string $message): bool
    {
        $sentKey = "urgent-message:{$key}:sent";
        $claimKey = "urgent-message:{$key}:sending";

        if (Cache::has($sentKey)) {
            return false;
        }

        if (! Cache::add($claimKey, true, self::CLAIM_SECONDS)) {
            throw new RuntimeException("Urgent message {$key} is being sent by another delivery.");
        }

        try {
            $this->gateway->send($phone, $message);
            Cache::put($sentKey, true, now()->addDays(self::REMEMBER_DAYS));
        } finally {
            Cache::forget($claimKey);
        }

        return true;
    }
}
