import type { BatchSlot } from './types';

export interface RecurrenceRule {
    /** ISO weekdays: 1 is Monday, 7 is Sunday. */
    weekdays: number[];
    startDate: string;
    endMode: 'count' | 'until';
    count: number;
    until: string;
}

export interface TimeRange {
    id: number;
    start: string;
    end: string;
}

export const WEEKDAYS = [1, 2, 3, 4, 5, 6, 7];

const DAY_MS = 86_400_000;

/** Dates are handled as UTC midnights so clock changes never skip or repeat a day. */
function parseDay(date: string): number {
    return Date.UTC(
        Number(date.slice(0, 4)),
        Number(date.slice(5, 7)) - 1,
        Number(date.slice(8, 10)),
    );
}

const formatDay = (day: number) => new Date(day).toISOString().slice(0, 10);

export function isoWeekday(date: string): number {
    return ((new Date(parseDay(date)).getUTCDay() + 6) % 7) + 1;
}

/**
 * The dates matching the rule, in order, never more than the batch can hold.
 */
export function generateDates(
    rule: RecurrenceRule,
    maxDates: number,
): string[] {
    if (!rule.startDate || rule.weekdays.length === 0) {
        return [];
    }

    const limit =
        rule.endMode === 'count'
            ? Math.min(Math.max(rule.count, 0), maxDates)
            : maxDates;
    const last =
        rule.endMode === 'until' && rule.until
            ? parseDay(rule.until)
            : Number.POSITIVE_INFINITY;
    const dates: string[] = [];

    for (
        let day = parseDay(rule.startDate);
        dates.length < limit && day <= last;
        day += DAY_MS
    ) {
        const date = formatDay(day);

        if (rule.weekdays.includes(isoWeekday(date))) {
            dates.push(date);
        }
    }

    return dates;
}

/** Every range ends after it starts, and no two overlap. */
export function rangesAreValid(ranges: TimeRange[]): boolean {
    const sorted = ranges.toSorted((a, b) => a.start.localeCompare(b.start));

    return sorted.every(
        (range, index) =>
            range.start !== '' &&
            range.end > range.start &&
            (index === 0 || sorted[index - 1].end <= range.start),
    );
}

/** One slot per date and range, in time order. */
export function buildSlots(dates: string[], ranges: TimeRange[]): BatchSlot[] {
    const sortedRanges = ranges.toSorted((a, b) =>
        a.start.localeCompare(b.start),
    );

    return dates.toSorted().flatMap((date) =>
        sortedRanges.map((range) => ({
            starts_at: `${date} ${range.start}`,
            ends_at: `${date} ${range.end}`,
        })),
    );
}

const minutesOf = (time: string) =>
    Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5));

export function slotMinutes(slot: BatchSlot): number {
    return (
        minutesOf(slot.ends_at.slice(11)) - minutesOf(slot.starts_at.slice(11))
    );
}
