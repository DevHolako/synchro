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
    /** Starts at 1; each emergency reschedule adds one. */
    revision: number;
    last_reschedule_reason: string | null;
    /** By the school's clock. */
    has_started: boolean;
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
    rooms: ExamRoom[];
    /** The viewer's seat, when they sit the exam. */
    my_seat: { room: string; seat: number; convocation_ready: boolean } | null;
    /** The viewer's room and role, when they invigilate it. */
    my_invigilation: {
        assignment_id: number;
        room: string;
        role: InvigilatorRole;
    } | null;
    /** The door lists and attendance sheets; null when the viewer may not download them. */
    roster: 'ready' | 'pending' | null;
}

export type InvigilatorRole = 'principal' | 'adjoint';

export interface ExamRoom {
    id: number;
    name: string;
    students_count: number;
    first_surname: string | null;
    last_surname: string | null;
    has_lead: boolean;
}

/** The rooms and invigilators sheet's data (GET /exams/{exam}/allocation). */
export interface ExamAllocation {
    state: ExamState;
    force_single_room: boolean;
    students_count: number;
    assistant_threshold: number;
    assignments: AllocatedRoom[];
    rooms: {
        id: number;
        name: string;
        building: string;
        exam_capacity: number;
        /** Held by a course session or another booked exam at that time. */
        busy: boolean;
    }[];
    teachers: { id: number; name: string }[];
}

export interface AllocatedRoom {
    id: number;
    room_id: number;
    room: string;
    building: string;
    exam_capacity: number;
    allocated_students_count: number;
    first_surname: string | null;
    last_surname: string | null;
    invigilators: { teacher_id: number; name: string; role: InvigilatorRole }[];
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
