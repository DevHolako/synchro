<?php

namespace App\Actions\Exams;

use App\Services\UrgentMessages\UrgentMessageGateway;

class SendUrgentMessageAction
{
    public function __construct(private UrgentMessageGateway $gateway) {}

    public function execute(string $phone, string $message): void
    {
        $this->gateway->send($phone, $message);
    }
}
