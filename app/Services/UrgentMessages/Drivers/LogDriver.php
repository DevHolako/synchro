<?php

namespace App\Services\UrgentMessages\Drivers;

use App\Services\UrgentMessages\LogUrgentMessageGateway;

/**
 * Log driver writing formatted JSON urgent messages to Laravel logs.
 */
class LogDriver extends LogUrgentMessageGateway {}
