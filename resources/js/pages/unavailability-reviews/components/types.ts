import type {
    Unavailability,
    UnavailabilityStatus,
    UnavailabilityType,
} from '@/components/unavailabilities/types';

export interface ReviewableUnavailability extends Unavailability {
    teacher: { id: number; name: string; email: string };
}

export interface PaginatedUnavailabilities {
    data: ReviewableUnavailability[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface Teacher {
    id: number;
    name: string;
}

export interface ReviewFilters {
    status: UnavailabilityStatus | 'all';
    type: UnavailabilityType | '';
    teacher_id: string;
}

export interface ReviewStats {
    pending: number;
    approved: number;
    rejected: number;
}

export type ReviewDecision = Exclude<UnavailabilityStatus, 'pending'>;

export interface PendingReview {
    unavailability: ReviewableUnavailability;
    decision: ReviewDecision;
}
