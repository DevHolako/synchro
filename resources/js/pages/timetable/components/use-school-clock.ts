import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import { wallClockNow } from './calendar-utils';

/** A stable function giving the school's current wall-clock time (see wallClockNow). */
export function useSchoolClock(): () => string {
    const { scheduleTimezone } = usePage().props;

    return useCallback(
        () => wallClockNow(scheduleTimezone),
        [scheduleTimezone],
    );
}
