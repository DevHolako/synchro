export type DeliberationStatus =
    | 'not_started'
    | 'draft'
    | 'submitted'
    | 'locked';

export interface DeliberationPeriod {
    id: number;
    name: string;
    academic_year: string;
}

export interface DeliberationSheet {
    exam_id: number;
    module: string;
    teacher: string | null;
    start: string;
    status: DeliberationStatus;
    lines: number;
    class_average: string | null;
    pass_rate: string | null;
}

export type DeliberationStats = Record<DeliberationStatus, number>;

/** Board order, as `ListDeliberationsAction::ORDER`. */
export const DELIBERATION_STATUSES: DeliberationStatus[] = [
    'submitted',
    'draft',
    'not_started',
    'locked',
];
