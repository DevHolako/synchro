export type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused';

export const ATTENDANCE_STATUSES: AttendanceStatus[] = [
    'present',
    'absent',
    'late',
    'excused',
];

export interface RosterStudent {
    student_id: number;
    name: string;
    student_number: string | null;
    group: string | null;
    status: AttendanceStatus | null;
    remarks: string | null;
    module_absences: number;
    module_recorded: number;
    /** Absences as a whole percentage of the module's recorded sessions. */
    module_absence_rate: number;
}

export type AttendanceMark = {
    student_id: number;
    /** Null removes the student's mark. */
    status: AttendanceStatus | null;
    remarks: string | null;
};

/** What the sheet edits: a status (or none yet) and a remark per student. */
export type DraftMarks = Record<
    number,
    { status: AttendanceStatus | null; remarks: string }
>;
