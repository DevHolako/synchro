import { router } from '@inertiajs/react';
import { index } from '@/routes/timetable';
import type {
    ScopePerspective,
    TimetableFilters,
    TimetableView,
} from './types';

/** Moving to another period only needs that period's sessions. */
const PERIOD_PROPS = ['sessions', 'filters'];

function toQuery(filters: TimetableFilters) {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== null),
    );
}

/**
 * The timetable's URL is its state: every change of period, perspective or subject is a visit.
 */
export function useTimetableNavigation(filters: TimetableFilters) {
    const visit = (next: TimetableFilters, only?: string[]) =>
        router.get(index.url(), toQuery(next), {
            preserveState: true,
            preserveScroll: true,
            ...(only ? { only, replace: true } : {}),
        });

    return {
        goToPeriod: (date: string, view: TimetableView) =>
            visit({ ...filters, date, view }, PERIOD_PROPS),
        goToDate: (date: string) => visit({ ...filters, date }),
        changePerspective: (perspective: ScopePerspective) =>
            visit({ ...filters, perspective, id: null }),
        changeSubject: (id: number | null) => visit({ ...filters, id }),
    };
}
