<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * The school's wall clock (`app.schedule_timezone`), in the same form as stored session times.
 *
 * Sessions are stored as local wall-clock times in the UTC application zone, so "now" must be
 * the school's local time read the same way before the two can be compared.
 */
final class SchoolClock
{
    /** How wall-clock times travel to the frontend: offset-less, so they render as they are. */
    public const string WALL_CLOCK_FORMAT = 'Y-m-d\\TH:i:s';

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            CarbonImmutable::now((string) config('app.schedule_timezone'))->format('Y-m-d H:i:s'),
        );
    }

    public static function today(): CarbonImmutable
    {
        return self::now()->startOfDay();
    }
}
