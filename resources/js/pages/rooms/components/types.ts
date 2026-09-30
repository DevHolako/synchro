export interface Campus {
    id: number;
    name: string;
    code: string;
    city?: string | null;
    address?: string | null;
    is_active: boolean;
    buildings?: Building[];
}

export interface Building {
    id: number;
    campus_id: number;
    name: string;
    code?: string | null;
    is_active: boolean;
    campus?: Campus;
}

export interface Room {
    id: number;
    building_id: number;
    name: string;
    code?: string | null;
    floor?: number | null;
    course_capacity: number;
    exam_capacity: number;
    has_projector: boolean;
    is_lab: boolean;
    has_computers: boolean;
    has_sound_system: boolean;
    is_active: boolean;
    building?: Building;
}

export interface Stats {
    total_rooms: number;
    active_rooms: number;
    total_course_capacity: number;
    total_exam_capacity: number;
    total_campuses: number;
    total_buildings: number;
}

export interface Filters {
    search: string;
    campus_id: string;
    building_id: string;
    is_active: string;
    has_projector: boolean;
    is_lab: boolean;
    has_computers: boolean;
    has_sound_system: boolean;
}
