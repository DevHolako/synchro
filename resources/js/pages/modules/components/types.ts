export interface Teacher {
    id: number;
    name: string;
    email: string;
}

export interface Department {
    id: number;
    name: string;
    code: string;
}

export interface Program {
    id: number;
    department_id: number;
    name: string;
    code: string;
    program_modality: 'formation_initiale' | 'temps_amenage';
    department?: Department;
}

export interface Module {
    id: number;
    program_id: number;
    teacher_id?: number | null;
    name: string;
    code: string;
    total_hours: number;
    lecture_hours: number;
    tp_hours: number;
    continuous_assessment_weight: number;
    exam_weight: number;
    color_code: string;
    description?: string | null;
    is_active: boolean;
    program?: Program;
    teacher?: Teacher | null;
}

export interface ModuleStats {
    total_modules: number;
    active_modules: number;
    total_syllabus_hours: number;
    total_lecture_hours: number;
    total_tp_hours: number;
    assigned_modules: number;
}

export interface ModuleFilters {
    search: string;
    program_id: string;
    teacher_id: string;
    is_active: string;
}

export interface ModuleLimits {
    max_continuous_assessment_weight: number;
}
