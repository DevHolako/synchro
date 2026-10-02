export interface DashboardStats {
    unread_notifications: number;
    sessions_this_week?: number;
    upcoming_exams?: number;
    active_rooms?: number;
    active_groups?: number;
    pending_unavailabilities?: number;
    my_unavailabilities?: number;
    my_grades_count?: number;
}

export interface DashboardSessionItem {
    id: number;
    module_name: string;
    module_code: string;
    color_code: string;
    starts_at: string;
    ends_at: string;
    room_name: string;
    building_name?: string | null;
    teacher_name: string;
    groups: string[];
}

export interface DashboardExamItem {
    id: number;
    module_name: string;
    module_code: string;
    color_code: string;
    starts_at: string;
    ends_at: string;
    state: string;
    period_name: string;
    rooms: string[];
}

export interface DashboardNotificationItem {
    id: string;
    type: string;
    data: {
        type?: string;
        title?: string;
        message?: string;
        timetable_url?: string;
        convocation_url?: string;
        [key: string]: unknown;
    };
    created_at: string;
    read_at: string | null;
}

export interface DashboardPermissions {
    canViewSchedules: boolean;
    canManageSchedules: boolean;
    canBrowseSchedules: boolean;
    canViewExams: boolean;
    canManageExams: boolean;
    canManageReferentials: boolean;
    canReviewUnavailability: boolean;
    canDeclareUnavailability: boolean;
    canEnterGrades: boolean;
    canViewOwnGrades: boolean;
    canViewUsers: boolean;
    canImportReferentials: boolean;
}

export interface DashboardPageProps {
    stats: DashboardStats;
    upcomingSessions: DashboardSessionItem[];
    upcomingExams: DashboardExamItem[];
    recentNotifications: DashboardNotificationItem[];
    permissions: DashboardPermissions;
}
