export type UnavailabilityType = 'recurring_weekly' | 'ad_hoc_date';

export type UnavailabilityStatus = 'pending' | 'approved' | 'rejected';

export type UnavailabilityPeriod = 'current' | 'past';

export interface Unavailability {
    id: number;
    teacher_id: number;
    type: UnavailabilityType;
    /** ISO-8601 weekday: 1 = Monday … 7 = Sunday. */
    day_of_week: number | null;
    /** Y-m-d */
    start_date: string;
    end_date: string | null;
    /** H:i */
    start_time: string | null;
    end_time: string | null;
    reason: string;
    status: UnavailabilityStatus;
    reviewed_at: string | null;
    review_note: string | null;
    created_at: string;
    reviewer: { id: number; name: string } | null;
}

export interface UnavailabilityFilters {
    period: UnavailabilityPeriod;
}

export const UNAVAILABILITY_TYPES: readonly UnavailabilityType[] = [
    'recurring_weekly',
    'ad_hoc_date',
];

export const UNAVAILABILITY_STATUSES: readonly UnavailabilityStatus[] = [
    'pending',
    'approved',
    'rejected',
];

export const WEEKDAYS: readonly number[] = [1, 2, 3, 4, 5, 6, 7];
