<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * The open scheduling grid (ADR 0004): 08:00 to 22:00, seven days a week, on quarter hours.
 *
 * The single source of these bounds for validation and messages; the frontend mirrors them
 * in `resources/js/lib/scheduling-grid.ts`.
 */
final class SchedulingGrid
{
    public const string START = '08:00';

    public const string END = '22:00';

    public const int STEP_MINUTES = 15;

    /**
     * Whether an `H:i` time of day is a grid line: between the bounds, inclusive, on a step.
     */
    public static function isGridTime(string $time): bool
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $parts) !== 1) {
            return false;
        }

        return $time >= self::START && $time <= self::END && (int) $parts[2] % self::STEP_MINUTES === 0;
    }

    /**
     * Whether a window lies inside the grid's daily bounds.
     */
    public static function contains(CarbonInterface $start, CarbonInterface $end): bool
    {
        return $start->format('H:i') >= self::START && $end->format('H:i') <= self::END;
    }

    /**
     * Whether a moment falls on a step of the grid.
     */
    public static function isOnStep(CarbonInterface $moment): bool
    {
        return $moment->minute % self::STEP_MINUTES === 0 && $moment->second === 0;
    }

    /**
     * The bounds as translation parameters.
     *
     * @return array{start: string, end: string}
     */
    public static function bounds(): array
    {
        return ['start' => self::START, 'end' => self::END];
    }
}
