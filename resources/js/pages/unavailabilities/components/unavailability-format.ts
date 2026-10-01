import type { Unavailability } from './types';

type Translate = (
    key: string,
    params?: Record<string, string | number>,
) => string;

/** Formats a Y-m-d date in the viewer's locale without shifting it across time zones. */
export function formatDay(value: string, locale: string): string {
    const [year, month, day] = value.split('-').map(Number);

    return new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(
        new Date(year, month - 1, day),
    );
}

/** "Every Friday, 18:00–22:00", "14:00–18:00 each day" or "Whole days". */
export function describeSlot(
    unavailability: Unavailability,
    t: Translate,
): string {
    const { start_time: start, end_time: end } = unavailability;

    if (unavailability.type === 'recurring_weekly') {
        return t('unavailabilities.slot_recurring', {
            day: t(`unavailabilities.day_${unavailability.day_of_week}`),
            start: start ?? '',
            end: end ?? '',
        });
    }

    return start && end
        ? t('unavailabilities.slot_time_window', { start, end })
        : t('unavailabilities.slot_whole_days');
}

/** "From 5 Oct 2026", "On 3 Nov 2026" or "3 Nov 2026 to 5 Nov 2026". */
export function describePeriod(
    unavailability: Unavailability,
    t: Translate,
    locale: string,
): string {
    const start = formatDay(unavailability.start_date, locale);

    if (!unavailability.end_date) {
        return t('unavailabilities.period_open_ended', { start });
    }

    if (unavailability.end_date === unavailability.start_date) {
        return t('unavailabilities.period_single_day', { date: start });
    }

    return t('unavailabilities.period_range', {
        start,
        end: formatDay(unavailability.end_date, locale),
    });
}
