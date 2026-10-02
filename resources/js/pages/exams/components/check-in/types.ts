export interface RoomCandidate {
    id: number;
    seat: number;
    name: string;
    student_number: string | null;
    /** `HH:mm`, school time; null until checked in. */
    checked_in_at: string | null;
}

export interface CheckInRoom {
    id: number;
    name: string;
    building: string;
    first_surname: string | null;
    last_surname: string | null;
}
