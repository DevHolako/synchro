<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The calendar views the timetable offers; values are FullCalendar's view names.
 *
 * Each view decides which dates it shows, so the server loads exactly what is on screen
 * and the client cannot ask for an arbitrary range.
 */
enum TimetableView: string
{
    case Day = 'timeGridDay';
    case Week = 'timeGridWeek';
    case Month = 'dayGridMonth';
    case ListDay = 'listDay';
    case ListWeek = 'listWeek';

    /** Weeks shown by the month grid, which always renders six rows. */
    private const int MONTH_GRID_WEEKS = 6;

    /**
     * The first day of the period containing the date: the day, its week's Monday, or the 1st of its month.
     */
    public function anchor(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this) {
            self::Day, self::ListDay => $date->startOfDay(),
            self::Week, self::ListWeek => $date->startOfWeek(CarbonInterface::MONDAY),
            self::Month => $date->startOfMonth(),
        };
    }

    /**
     * The half-open [from, until) range of dates the view displays around the date.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonImmutable $date): array
    {
        $anchor = $this->anchor($date);

        return match ($this) {
            self::Day, self::ListDay => [$anchor, $anchor->addDay()],
            self::Week, self::ListWeek => [$anchor, $anchor->addWeek()],
            self::Month => [
                $from = $anchor->startOfWeek(CarbonInterface::MONDAY),
                $from->addWeeks(self::MONTH_GRID_WEEKS),
            ],
        };
    }
}
