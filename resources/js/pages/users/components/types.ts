export type UserRole = 'administrator' | 'coordinator' | 'teacher' | 'student';

export type AccountStatus = 'invited' | 'active';

export interface Department {
    id: number;
    name: string;
    code: string;
}

export interface StudentGroup {
    id: number;
    name: string;
    code: string | null;
    academic_year: string;
}

export interface Invitation {
    id: number;
    expires_at: string;
    consumed_at: string | null;
    revoked_at: string | null;
    created_at: string;
}

export interface ManagedUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    status: AccountStatus;
    activated_at: string | null;
    latest_invitation: Invitation | null;
    teacher_profile: {
        employee_number: string | null;
        department: Department | null;
    } | null;
    student_profile: {
        student_number: string | null;
        student_group: Pick<StudentGroup, 'id' | 'name' | 'code'> | null;
    } | null;
}

export interface PaginatedUsers {
    data: ManagedUser[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface UserFilters {
    search: string;
    role: UserRole | '';
    status: AccountStatus | '';
}

export interface UserStats {
    total: number;
    active: number;
    invited: number;
    teachers: number;
    students: number;
}

export interface UserAbilities {
    provision: boolean;
    issue_temporary_password: boolean;
}

export interface TemporaryPasswordFlash {
    name: string;
    email: string;
    password: string;
}

export const USER_ROLES: readonly UserRole[] = [
    'administrator',
    'coordinator',
    'teacher',
    'student',
];

export const ACCOUNT_STATUSES: readonly AccountStatus[] = ['active', 'invited'];
