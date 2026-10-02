export interface CheckInCandidate {
    id: number;
    name: string;
    student_number: string | null;
    group: string | null;
    room: string;
    building: string;
    seat: number;
}

export interface CheckInExam {
    id: number;
    module: string;
    /** Offset-less wall-clock time, `2026-10-12T09:00:00`. */
    start: string;
    end: string;
}

export interface CheckInState {
    open: boolean;
    opens_at: string;
    /** `HH:mm`, school time. */
    checked_in_at: string | null;
    checked_in_by: string | null;
    /** The scanning invigilator watches another room. */
    wrong_room: boolean;
    can_check_in: boolean;
    can_undo: boolean;
}
