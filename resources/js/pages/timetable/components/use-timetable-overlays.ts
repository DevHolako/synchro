import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { SchedulingOptions } from './schedule/types';
import type { TimetableSession } from './types';
import { useSessionReschedule } from './use-session-reschedule';

/**
 * Which of the timetable's dialogs are open (the scheduling wizard, a drop's soft-conflict
 * prompt, the session details, the register, the calendar link), and how to open them.
 */
export function useTimetableOverlays(
    schedulingOptions: SchedulingOptions | null | undefined,
) {
    const [scheduling, setScheduling] = useState(false);
    const [subscribing, setSubscribing] = useState(false);
    const [selectedSession, setSelectedSession] =
        useState<TimetableSession | null>(null);
    const [attendanceSession, setAttendanceSession] =
        useState<TimetableSession | null>(null);
    const reschedule = useSessionReschedule();

    return {
        scheduling,
        subscribing,
        selectedSession,
        attendanceSession,
        reschedule,
        /** The user is in the middle of something polling must not redraw. */
        editing:
            scheduling ||
            reschedule.pending !== null ||
            attendanceSession !== null,
        openScheduling: () => {
            setScheduling(true);

            // The wizard's options are loaded the first time it opens.
            if (schedulingOptions === undefined) {
                router.reload({ only: ['schedulingOptions'] });
            }
        },
        closeScheduling: () => setScheduling(false),
        openSubscription: () => setSubscribing(true),
        closeSubscription: () => setSubscribing(false),
        selectSession: setSelectedSession,
        openAttendance: setAttendanceSession,
        closeAttendance: () => setAttendanceSession(null),
    };
}

export type TimetableOverlayState = ReturnType<typeof useTimetableOverlays>;
