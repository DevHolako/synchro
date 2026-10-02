/**
 * Formatting for the offset-less wall-clock strings the timetable uses
 * (`2026-10-03T08:30:00` or `2026-10-03 08:30`).
 */

/** The `HH:mm` part. */
export const timeOf = (value: string) => value.slice(11, 16);

/** The day in the UI language; read as UTC so it is never shifted a day. */
export function formatDay(
    value: string,
    locale: string,
    style: 'full' | 'medium' = 'full',
): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: style,
        timeZone: 'UTC',
    }).format(new Date(`${value.slice(0, 10)}T00:00:00Z`));
}

/** Minutes as hours in the UI language, e.g. `22,5`. */
export function formatHours(minutes: number, locale: string): string {
    return new Intl.NumberFormat(locale, { maximumFractionDigits: 2 }).format(
        minutes / 60,
    );
}
