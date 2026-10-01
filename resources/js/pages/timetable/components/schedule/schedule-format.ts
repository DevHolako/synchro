/** A `Y-m-d` date in the UI language; read as UTC so it is never shifted a day. */
export function formatDay(
    date: string,
    locale: string,
    style: 'full' | 'medium' = 'full',
): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: style,
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));
}

export function formatHours(minutes: number, locale: string): string {
    return new Intl.NumberFormat(locale, { maximumFractionDigits: 2 }).format(
        minutes / 60,
    );
}
