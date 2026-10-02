import { AttendanceSheet } from './attendance/attendance-sheet';
import { CalendarFeedDialog } from './calendar-feed-dialog';
import type { CalendarFeedLinks } from './calendar-feed-dialog';
import { ScheduleSessionsDialog } from './schedule/schedule-sessions-dialog';
import type { SchedulingOptions, SchedulingPrefill } from './schedule/types';
import { SessionDetailsDialog } from './session-details-dialog';
import { SoftConflictDialog } from './soft-conflict-dialog';
import type { TimetableSession } from './types';
import type { useSessionReschedule } from './use-session-reschedule';

interface TimetableOverlaysProps {
    scheduling: boolean;
    schedulingOptions: SchedulingOptions | null | undefined;
    prefill: SchedulingPrefill;
    canSchedule: boolean;
    canOverride: boolean;
    canRecordAttendance: (session: TimetableSession) => boolean;
    reschedule: ReturnType<typeof useSessionReschedule>;
    selectedSession: TimetableSession | null;
    attendanceSession: TimetableSession | null;
    onCloseScheduling: () => void;
    onScheduled: (firstDate: string) => void;
    onCloseDetails: () => void;
    onOpenAttendance: (session: TimetableSession) => void;
    onCloseAttendance: () => void;
    subscribing: boolean;
    calendarFeed: CalendarFeedLinks | null;
    onCloseSubscription: () => void;
}

/** Everything the timetable opens on top of the calendar. */
export function TimetableOverlays({
    scheduling,
    schedulingOptions,
    prefill,
    canSchedule,
    canOverride,
    canRecordAttendance,
    reschedule,
    selectedSession,
    attendanceSession,
    onCloseScheduling,
    onScheduled,
    onCloseDetails,
    onOpenAttendance,
    onCloseAttendance,
    subscribing,
    calendarFeed,
    onCloseSubscription,
}: TimetableOverlaysProps) {
    return (
        <>
            {scheduling ? (
                <ScheduleSessionsDialog
                    options={schedulingOptions}
                    prefill={prefill}
                    canOverride={canOverride}
                    onClose={onCloseScheduling}
                    onScheduled={onScheduled}
                />
            ) : null}

            {reschedule.pending?.softConflicts ? (
                <SoftConflictDialog
                    key={`${reschedule.pending.session.id}-${reschedule.pending.startsAt}`}
                    conflicts={reschedule.pending.softConflicts}
                    canOverride={canOverride}
                    saving={reschedule.saving}
                    onConfirm={reschedule.confirm}
                    onCancel={reschedule.cancel}
                />
            ) : null}

            {attendanceSession ? (
                <AttendanceSheet
                    key={attendanceSession.id}
                    session={attendanceSession}
                    onClose={onCloseAttendance}
                />
            ) : null}

            {subscribing ? (
                <CalendarFeedDialog
                    feed={calendarFeed}
                    onClose={onCloseSubscription}
                />
            ) : null}

            <SessionDetailsDialog
                session={selectedSession}
                canDelete={canSchedule}
                canRecordAttendance={canRecordAttendance}
                onOpenAttendance={onOpenAttendance}
                onClose={onCloseDetails}
            />
        </>
    );
}
