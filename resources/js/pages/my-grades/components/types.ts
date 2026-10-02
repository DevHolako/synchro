/** A published grade: a line of a locked deliberation. */
export interface OwnGrade {
    exam_id: number;
    module: string;
    period: string;
    academic_year: string;
    start: string;
    continuous_assessment_weight: number;
    continuous_assessment_grade: string | null;
    exam_grade: string | null;
    is_absent: boolean;
    final_grade: string | null;
    passed: boolean;
}
