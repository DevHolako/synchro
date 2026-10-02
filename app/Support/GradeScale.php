<?php

namespace App\Support;

/**
 * The grading scale (spec 05): grades out of 20 with two decimals, compared and computed on
 * integer hundredths so no float drift reaches a stored grade.
 */
final class GradeScale
{
    /** The highest grade. */
    public const int MAX = 20;

    /** The lowest passing final grade: below it, the student sits the retake. */
    public const string PASS_MARK = '10.00';

    /**
     * A grade with at most two decimals, in hundredths ("14.50" → 1450).
     */
    public static function toHundredths(string $grade): int
    {
        return (int) round((float) $grade * 100);
    }

    /**
     * Hundredths written as a two-decimal grade (1450 → "14.50").
     */
    public static function fromHundredths(int $hundredths): string
    {
        return sprintf('%d.%02d', intdiv($hundredths, 100), $hundredths % 100);
    }

    /**
     * Whether a final grade passes; a missing final does not.
     */
    public static function passes(?string $final): bool
    {
        return $final !== null && self::toHundredths($final) >= self::toHundredths(self::PASS_MARK);
    }
}
