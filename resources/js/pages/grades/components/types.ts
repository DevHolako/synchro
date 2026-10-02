export type { GradeSheetStatus } from '@/pages/exams/components/types';
import type { GradeSheetStatus } from '@/pages/exams/components/types';

export interface GradeSheetExam {
    id: number;
    module: string;
    period: string;
    start: string;
    end: string;
}

export interface GradeWeights {
    continuous_assessment: number;
    exam: number;
}

export interface GradeSheet {
    status: GradeSheetStatus;
    submitted_at: string | null;
    submitted_by: string | null;
}

/** One candidate's saved line, as the server stored it (grades with two decimals). */
export interface GradeRow {
    student_id: number;
    name: string;
    student_number: string | null;
    group: string | null;
    continuous_assessment_grade: string | null;
    exam_grade: string | null;
    final_grade: string | null;
    is_absent: boolean;
    remarks: string | null;
}

/** A line as typed in the grid. */
export interface GradeDraft {
    continuousAssessment: string;
    exam: string;
    absent: boolean;
    remarks: string;
}

export interface DeliberationStats {
    graded: number;
    average: string | null;
    median: string | null;
    pass_rate: string | null;
    passing: number;
    failing: number;
    absent: number;
}

/** The coordinator's side of the sheet: figures, send-back, lock and PV. */
export interface Deliberation {
    stats: DeliberationStats;
    returned_at: string | null;
    return_reason: string | null;
    locked_at: string | null;
    locked_by: string | null;
    /** Null when the sheet is not locked or the viewer may not download it. */
    pv: 'ready' | 'pending' | null;
    /** The sheet is submitted and the viewer may lock it or send it back. */
    can_decide: boolean;
}
