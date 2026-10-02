export interface SchedulingModule {
    id: number;
    program_id: number;
    teacher_id: number | null;
    code: string;
    name: string;
    color_code: string;
    total_hours: number;
}

export interface SchedulingGroup {
    id: number;
    program_id: number;
    name: string;
    code: string | null;
    academic_year: string;
    expected_headcount: number;
}

export interface SchedulingRoom {
    id: number;
    name: string;
    building: string;
    course_capacity: number;
}

export interface SchedulingOptions {
    modules: SchedulingModule[];
    groups: SchedulingGroup[];
    rooms: SchedulingRoom[];
    teachers: { id: number; name: string }[];
}

/** What the wizard opens with, taken from the timetable on screen. */
export interface SchedulingPrefill {
    moduleId: number | null;
    groupId: number | null;
    teacherId: number | null;
    roomId: number | null;
    date: string;
}

export interface Assignment {
    moduleId: number | null;
    groupIds: number[];
    /** Null means the module's own teacher. */
    teacherId: number | null;
    roomId: number | null;
}

/** A session-to-be, in the server's `Y-m-d H:i` format. */
export type BatchSlot = {
    starts_at: string;
    ends_at: string;
};

export type BatchPayload = {
    module_id: number | null;
    teacher_id: number | null;
    room_id: number | null;
    student_group_ids: number[];
    slots: BatchSlot[];
};

export type ConflictKind =
    | 'room'
    | 'teacher'
    | 'group'
    | 'capacity'
    | 'unavailability';

export interface SlotConflict {
    type: ConflictKind;
    resource_id: number;
    resource_name: string;
    /** The colliding booking, for hard conflicts. */
    booking_type: 'course_session' | 'exam' | null;
    booking_id: number | null;
    starts_at: string;
    ends_at: string;
    details: Record<string, string | number | null>;
}

export interface CheckedSlot {
    starts_at: string;
    ends_at: string;
    index: number;
    overlaps: number[];
    has_hard_conflicts: boolean;
    hard_conflicts: SlotConflict[];
    has_soft_conflicts: boolean;
    soft_conflicts: SlotConflict[];
}

export interface BatchCheckResponse {
    slots: CheckedSlot[];
    syllabus: {
        total_hours: number;
        batch_minutes: number;
        groups: { id: number; name: string; planned_minutes: number }[];
    };
}
