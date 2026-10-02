import type { ExamState } from '@/pages/exams/components/types';

export interface RetakePeriodOption {
    id: number;
    name: string;
    academic_year: string;
    session_type: 'normal' | 'rattrapage';
}

export interface RetakePeriod extends RetakePeriodOption {
    start_date: string;
    end_date: string;
}

export interface RetakeStudent {
    student_id: number;
    name: string;
    student_number: string | null;
    group: string | null;
    final_grade: string | null;
}

/** One module's failing students and its retake exam, once created. */
export interface RetakeModule {
    module_id: number;
    module: string;
    group_ids: number[];
    /** Failing students without a group: no exam can seat them. */
    ungrouped: number;
    exam: { id: number; state: ExamState } | null;
    students: RetakeStudent[];
}
