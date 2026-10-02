import type { SlotConflict } from '@/pages/timetable/components/schedule/types';

// Mirrors App\Enums\ExamState (PHP).
export const EXAM_STATES = [
    'draft',
    'scheduled',
    'published',
    'completed',
    'archived',
] as const;

export type ExamState = (typeof EXAM_STATES)[number];

export type ExamPeriodStatus = 'upcoming' | 'ongoing' | 'ended' | 'archived';

export type ExamSessionType = 'normal' | 'rattrapage';

export interface ExamPeriod {
    id: number;
    name: string;
    session_type: ExamSessionType;
    academic_year: string;
    start_date: string;
    end_date: string;
    status: ExamPeriodStatus;
    exams_count: number;
}

export interface Exam {
    id: number;
    exam_period_id: number;
    /** Offset-less wall-clock time, `2026-10-12T09:00:00`. */
    start: string;
    end: string;
    state: ExamState;
    /** Still a draft or scheduled once its start has passed. */
    is_overdue: boolean;
    module: {
        id: number;
        program_id: number;
        code: string;
        name: string;
        color_code: string;
    };
    groups: { id: number; name: string }[];
}

export type ExamStats = Record<ExamState, number>;

export interface ExamFilters {
    state: ExamState | '';
    program_id: string;
    group_id: string;
}

export interface ExamOptions {
    programs: { id: number; code: string; name: string }[];
    modules: { id: number; program_id: number; code: string; name: string }[];
    groups: { id: number; program_id: number; name: string }[];
}

export type ExamListView = 'list' | 'calendar';

/** A lifecycle move or removal waiting for the coordinator's confirmation. */
export type ExamConfirmation =
    | { kind: 'schedule' | 'unschedule' | 'publish' | 'delete'; exam: Exam }
    | {
          kind: 'publish_period' | 'archive_period' | 'delete_period';
          period: ExamPeriod;
      };

export interface ExamCheckResponse {
    has_hard_conflicts: boolean;
    hard_conflicts: SlotConflict[];
}
