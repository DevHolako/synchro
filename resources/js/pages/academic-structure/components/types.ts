export type ProgramModality = 'temps_amenage' | 'formation_initiale';

export interface Campus {
    id: number;
    name: string;
    code: string;
    city?: string | null;
}

export interface Department {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    is_active: boolean;
    programs_count?: number;
    student_groups_count?: number;
}

export interface Program {
    id: number;
    department_id: number;
    name: string;
    code: string;
    program_modality: ProgramModality;
    description?: string | null;
    is_active: boolean;
    department?: Department;
    student_groups_count?: number;
    student_groups_sum_expected_headcount?: number | null;
}

export interface StudentGroup {
    id: number;
    program_id: number;
    campus_id?: number | null;
    name: string;
    code?: string | null;
    academic_year: string;
    expected_headcount: number;
    is_active: boolean;
    program?: Program;
    campus?: Campus;
}

export interface AcademicStats {
    total_departments: number;
    total_programs: number;
    programs_formation_initiale: number;
    programs_temps_amenage: number;
    total_groups: number;
    total_expected_headcount: number;
}

export interface AcademicFilters {
    search: string;
    department_id: string;
    program_modality: string;
    academic_year: string;
    is_active: string;
}
