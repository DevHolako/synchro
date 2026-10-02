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
