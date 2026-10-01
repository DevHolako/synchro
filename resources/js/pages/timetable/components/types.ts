export type TimetablePerspective =
    | 'mine'
    | 'campus'
    | 'group'
    | 'teacher'
    | 'room';

/** A resolved scope never holds "mine": it is the viewer's group or own teaching. */
export type ScopePerspective = Exclude<TimetablePerspective, 'mine'>;

/** FullCalendar view names, mirroring App\Enums\TimetableView. */
export type TimetableView =
    | 'timeGridDay'
    | 'timeGridWeek'
    | 'dayGridMonth'
    | 'listDay'
    | 'listWeek';

export type OverrideType = 'capacity' | 'unavailability';

export interface TimetableSession {
    id: number;
    /** Local wall-clock time without an offset, e.g. 2026-10-03T08:30:00. */
    start: string;
    end: string;
    module: { id: number; code: string; name: string; color_code: string };
    teacher: { id: number; name: string };
    room: { id: number; name: string; building: string };
    groups: { id: number; name: string; code: string | null }[];
    overrides: { id: number; type: OverrideType; justification: string }[];
}

export interface TimetableFilters {
    perspective: TimetablePerspective;
    id: number | null;
    /** First day of the displayed period (Y-m-d). */
    date: string;
    view: TimetableView | null;
}

export interface TimetableScope {
    perspective: ScopePerspective;
    id: number | null;
}

export interface TimetableOptions {
    groups: {
        id: number;
        name: string;
        code: string | null;
        academic_year: string;
    }[];
    teachers: { id: number; name: string }[];
    rooms: {
        id: number;
        name: string;
        code: string | null;
        building: string;
    }[];
    campuses: { id: number; name: string; code: string }[];
}

export interface SyllabusProgress {
    module_id: number;
    code: string;
    name: string;
    color_code: string;
    total_hours: number;
    planned_minutes: number;
}

export const BROWSE_PERSPECTIVES: ScopePerspective[] = [
    'campus',
    'group',
    'teacher',
    'room',
];
