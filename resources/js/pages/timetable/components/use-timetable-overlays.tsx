import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Permission } from '@/lib/permissions';
import { AttendanceSheet } from './attendance/attendance-sheet';
import { CalendarFeedDialog } from './calendar-feed-dialog';
import type { CalendarFeedLinks } from './calendar-feed-dialog';
import { ScheduleSessionsDialog } from './schedule/schedule-sessions-dialog';
import type { SchedulingOptions, SchedulingPrefill } from './schedule/types';
import { SessionDetailsDialog } from './session-details-dialog';
import { SoftConflictDialog } from './soft-conflict-dialog';
import type { TimetableLimits, TimetableSession } from './types';
import { useSessionReschedule } from './use-session-reschedule';

interface UseTimetableOverlaysArgs {
    schedulingOptions: SchedulingOptions | null | undefined;
    prefill: SchedulingPrefill;
    canSchedule: boolean;
    limits: TimetableLimits;
    calendarFeed: CalendarFeedLinks | null;
    /** After a batch is saved, with the first new session's date. */
    onScheduled: (firstDate: string) => void;
}

/**
 * Everything the timetable opens on top of the calendar (the scheduling wizard, the
 * soft-conflict prompt of a drop, the session details, the register, the calendar link),
 * with the state that drives them.
 */
export function useTimetableOverlays({
    schedulingOptions,
    prefill,
    canSchedule,
    limits,
    calendarFeed,
    onScheduled,
}: UseTimetableOverlaysArgs) {
    const { auth } = usePage().props;
    const [scheduling, setScheduling] = useState(false);
    const [subscribing, setSubscribing] = useState(false);
    const [selectedSession, setSelectedSession] =
        useState<TimetableSession | null>(null);
    const [attendanceSession, setAttendanceSession] =
        useState<TimetableSession | null>(null);
    const reschedule = useSessionReschedule();
    const canOverride = auth.permissions.includes(
        Permission.OverrideSoftConflicts,
    );

    const openScheduling = () => {
        setScheduling(true);

        if (schedulingOptions === undefined) {
            router.reload({ only: ['schedulingOptions'] });
        }
    };

    const element = (
        <>
            {scheduling ? (
                <ScheduleSessionsDialog
                    options={schedulingOptions}
                    limits={limits}
                    prefill={prefill}
                    canOverride={canOverride}
                    onClose={() => setScheduling(false)}
                    onScheduled={(firstDate) => {
                        setScheduling(false);
                        onScheduled(firstDate);
                    }}
                />
            ) : null}

            {reschedule.pending?.softConflicts ? (
                <SoftConflictDialog
                    key={`${reschedule.pending.session.id}-${reschedule.pending.startsAt}`}
                    conflicts={reschedule.pending.softConflicts}
                    canOverride={canOverride}
                    limits={limits}
                    saving={reschedule.saving}
                    onConfirm={reschedule.confirm}
                    onCancel={reschedule.cancel}
                />
            ) : null}

            {attendanceSession ? (
                <AttendanceSheet
                    key={attendanceSession.id}
                    session={attendanceSession}
                    onClose={() => setAttendanceSession(null)}
                />
            ) : null}

            {subscribing ? (
                <CalendarFeedDialog
                    feed={calendarFeed}
                    onClose={() => setSubscribing(false)}
                />
            ) : null}

            <SessionDetailsDialog
                session={selectedSession}
                canDelete={canSchedule}
                onOpenAttendance={setAttendanceSession}
                onClose={() => setSelectedSession(null)}
            />
        </>
    );

    return {
        element,
        /** The user is in the middle of something polling must not redraw. */
        editing:
            scheduling ||
            reschedule.pending !== null ||
            attendanceSession !== null,
        savingSessionId: reschedule.pending?.session.id ?? null,
        moveSession: reschedule.move,
        selectSession: setSelectedSession,
        openScheduling,
        openSubscription: () => setSubscribing(true),
    };
}
