export type UnavailabilityPeriod = 'current' | 'past';

export interface UnavailabilityFilters {
    period: UnavailabilityPeriod;
}
