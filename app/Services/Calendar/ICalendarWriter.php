<?php

namespace App\Services\Calendar;

use Carbon\CarbonInterface;

/**
 * A minimal RFC 5545 writer for published, read-only calendars: VEVENTs with UTC times.
 */
final class ICalendarWriter
{
    private const string CRLF = "\r\n";

    /** Content lines are folded at 75 octets (RFC 5545 §3.1). */
    private const int LINE_OCTETS = 75;

    private const string UTC_FORMAT = 'Ymd\THis\Z';

    /** @var list<string> */
    private array $events = [];

    public function __construct(private readonly string $name) {}

    public function addEvent(
        string $uid,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        string $summary,
        string $location,
        string $description,
        int $sequence,
        CarbonInterface $lastModified,
    ): void {
        $this->events[] = implode(self::CRLF, array_map($this->fold(...), [
            'BEGIN:VEVENT',
            'UID:'.$this->escape($uid),
            'DTSTAMP:'.$this->utc($lastModified),
            'LAST-MODIFIED:'.$this->utc($lastModified),
            'SEQUENCE:'.$sequence,
            'DTSTART:'.$this->utc($startsAt),
            'DTEND:'.$this->utc($endsAt),
            'SUMMARY:'.$this->escape($summary),
            'LOCATION:'.$this->escape($location),
            'DESCRIPTION:'.$this->escape($description),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'END:VEVENT',
        ]));
    }

    public function render(): string
    {
        $header = array_map($this->fold(...), [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ISGA//Synchro//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape($this->name),
        ]);

        return implode(self::CRLF, [...$header, ...$this->events, 'END:VCALENDAR']).self::CRLF;
    }

    private function utc(CarbonInterface $moment): string
    {
        return $moment->copy()->utc()->format(self::UTC_FORMAT);
    }

    /**
     * TEXT values escape backslashes, semicolons, commas and newlines (RFC 5545 §3.3.11).
     */
    private function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\\,', '\\n', '\\n'], $text);
    }

    /**
     * Splits a line into 75-octet chunks, never inside a multibyte character, continuing with a space.
     */
    private function fold(string $line): string
    {
        $chunks = [];
        $current = '';

        foreach (mb_str_split($line) as $character) {
            $limit = $chunks === [] ? self::LINE_OCTETS : self::LINE_OCTETS - 1;

            if (strlen($current.$character) > $limit) {
                $chunks[] = $current;
                $current = '';
            }

            $current .= $character;
        }

        $chunks[] = $current;

        return implode(self::CRLF.' ', $chunks);
    }
}
