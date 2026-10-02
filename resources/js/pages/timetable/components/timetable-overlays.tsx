import { usePage } from '@inertiajs/react';
import { Permission } from '@/lib/permissions';
import { AttendanceSheet } from './attendance/attendance-sheet';
import { CalendarFeedDialog } from './calendar-feed-dialog';
import type { CalendarFeedLinks } from './calendar-feed-dialog';
import { ScheduleSessionsDialog } from './schedule/schedule-sessions-dialog';
import type { SchedulingOptions, SchedulingPrefill } from './schedule/types';
import { SessionDetailsDialog } from './session-details-dialog';
import { SoftConflictDialog } from './soft-conflict-dialog';
import type { TimetableLimits } from './types';
import type { TimetableOverlayState } from './use-timetable-overlays';

interface TimetableOverlaysProps {
    overlays: TimetableOverlayState;
    schedulingOptions: SchedulingOptions | null | undefined;
    prefill: SchedulingPrefill;
    canSchedule: boolean;
    limits: TimetableLimits;
    calendarFeed: CalendarFeedLinks | null;
    /** After a batch is saved, with the first new session's date. */
    onScheduled: (firstDate: string) => void;
}

/** The dialogs the timetable opens on top of the calendar. */
export function TimetableOverlays({
    overlays,
    schedulingOptions,
    prefill,
    canSchedule,
    limits,
    calendarFeed,
    onScheduled,
}: TimetableOverlaysProps) {
    const { auth } = usePage().props;
    const canOverride = auth.permissions.includes(
        Permission.OverrideSoftConflicts,
    );
    const { reschedule } = overlays;

    return (
        <>
            {overlays.scheduling ? (
                <ScheduleSessionsDialog
                    options={schedulingOptions}
                    limits={limits}
                    prefill={prefill}
                    canOverride={canOverride}
                    onClose={overlays.closeScheduling}
                    onScheduled={(firstDate) => {
                        overlays.closeScheduling();
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

            {overlays.attendanceSession ? (
                <AttendanceSheet
                    key={overlays.attendanceSession.id}
                    session={overlays.attendanceSession}
                    onClose={overlays.closeAttendance}
                />
            ) : null}

            {overlays.subscribing ? (
                <CalendarFeedDialog
                    feed={calendarFeed}
                    onClose={overlays.closeSubscription}
                />
            ) : null}

            <SessionDetailsDialog
                session={overlays.selectedSession}
                canDelete={canSchedule}
                onOpenAttendance={overlays.openAttendance}
                onClose={() => overlays.selectSession(null)}
            />
        </>
    );
}
