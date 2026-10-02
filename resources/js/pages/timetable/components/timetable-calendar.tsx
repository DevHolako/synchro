import type {
    DatesSetArg,
    EventClickArg,
    EventContentArg,
    EventDropArg,
} from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import type { EventResizeDoneArg } from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { useEffect, useMemo, useRef } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/i18n/LanguageContext';
import { GRID_END, GRID_START } from '@/lib/scheduling-grid';
import { hasStarted, toCalendarEvent, toWallClock } from './calendar-utils';
import { useSchoolClock } from './use-school-clock';
import type { PendingMove } from './use-session-reschedule';
import { SessionEventContent } from './session-event-content';
import type {
    ScopePerspective,
    TimetableSession,
    TimetableView,
} from './types';

const PLUGINS = [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin];
/** Moving keeps a month cell's times; resizing only makes sense on a time grid. */
const VIEW_OPTIONS = { dayGridMonth: { eventDurationEditable: false } };
const SNAP = '00:15:00';
const LOCALES = [frLocale];

const DESKTOP_TOOLBAR = {
    left: 'prev,next today',
    center: 'title',
    right: 'timeGridDay,timeGridWeek,dayGridMonth,listWeek',
};
const MOBILE_TOOLBAR = {
    left: 'prev,next',
    center: 'title',
    right: 'listWeek,timeGridDay',
};

const MONDAY = 1;

interface TimetableCalendarProps {
    sessions: TimetableSession[];
    perspective: ScopePerspective;
    date: string;
    view: TimetableView;
    activeModuleId: number | null;
    /** Whether the user may drag and resize sessions. */
    canEdit: boolean;
    /** The session whose move is being saved; nothing else moves meanwhile. */
    savingSessionId: number | null;
    onPeriodChange: (date: string, view: TimetableView) => void;
    onSessionClick: (session: TimetableSession) => void;
    onMove: (move: PendingMove) => void;
    onInteractionChange: (interacting: boolean) => void;
}

function sessionOf(event: { extendedProps: Record<string, unknown> }) {
    return event.extendedProps.session as TimetableSession;
}

export function TimetableCalendar({
    sessions,
    perspective,
    date,
    view,
    activeModuleId,
    canEdit,
    savingSessionId,
    onPeriodChange,
    onSessionClick,
    onMove,
    onInteractionChange,
}: TimetableCalendarProps) {
    const { locale } = useTranslation();
    const isMobile = useIsMobile();
    const schoolNow = useSchoolClock();
    const containerRef = useRef<HTMLDivElement>(null);
    const calendarRef = useRef<FullCalendar>(null);

    // FullCalendar only re-measures on window resizes; the sidebar and the syllabus panel
    // change its width without one.
    useEffect(() => {
        const container = containerRef.current;

        if (!container) {
            return;
        }

        const observer = new ResizeObserver(() =>
            calendarRef.current?.getApi().updateSize(),
        );
        observer.observe(container);

        return () => observer.disconnect();
    }, []);

    // The calendar only reads its initial date; follow later changes made outside it,
    // such as jumping to a batch of new sessions.
    useEffect(() => {
        const api = calendarRef.current?.getApi();

        if (api && api.view.currentStart.toISOString().slice(0, 10) !== date) {
            // FullCalendar re-renders synchronously; leave React's commit phase first.
            queueMicrotask(() => api.gotoDate(date));
        }
    }, [date]);

    // Editing stays off on phones, where a scroll could turn into a move.
    const editable = canEdit && !isMobile && savingSessionId === null;

    const events = useMemo(() => {
        const now = schoolNow();

        return sessions.map((session) =>
            toCalendarEvent(session, {
                dimmed:
                    activeModuleId !== null &&
                    session.module.id !== activeModuleId,
                editable: editable && !hasStarted(session, now),
                saving: session.id === savingSessionId,
            }),
        );
    }, [sessions, activeModuleId, editable, savingSessionId, schoolNow]);

    // Sessions cannot move into the past.
    const allowDrop = (span: { start: Date }) =>
        span.start >= new Date(`${schoolNow()}Z`);

    const handleChange = (arg: EventDropArg | EventResizeDoneArg) => {
        const { start, end } = arg.event;

        if (!start || !end) {
            arg.revert();

            return;
        }

        onMove({
            session: sessionOf(arg.event),
            startsAt: toWallClock(start),
            endsAt: toWallClock(end),
            revert: arg.revert,
        });
    };

    const renderEvent = (arg: EventContentArg) => (
        <SessionEventContent
            session={sessionOf(arg.event)}
            perspective={perspective}
            timeText={arg.timeText}
            compact={arg.view.type === 'dayGridMonth'}
        />
    );

    // The calendar runs in UTC, so its dates are the wall-clock dates of the period.
    const handleDatesSet = (arg: DatesSetArg) => {
        const start = arg.view.currentStart.toISOString().slice(0, 10);
        const type = arg.view.type as TimetableView;

        if (start !== date || type !== view) {
            onPeriodChange(start, type);
        }
    };

    const handleEventClick = (arg: EventClickArg) =>
        onSessionClick(sessionOf(arg.event));

    return (
        <div
            ref={containerRef}
            className="timetable-calendar rounded-lg border border-neutral-200 bg-white p-2 shadow-xs md:p-4 dark:border-neutral-800 dark:bg-neutral-900"
        >
            <FullCalendar
                ref={calendarRef}
                plugins={PLUGINS}
                locales={LOCALES}
                locale={locale}
                timeZone="UTC"
                now={schoolNow}
                initialView={view}
                initialDate={date}
                headerToolbar={isMobile ? MOBILE_TOOLBAR : DESKTOP_TOOLBAR}
                firstDay={MONDAY}
                slotMinTime={GRID_START}
                slotMaxTime={GRID_END}
                scrollTime={GRID_START}
                allDaySlot={false}
                nowIndicator
                height="auto"
                eventTimeFormat={{ hour: '2-digit', minute: '2-digit' }}
                events={events}
                eventContent={renderEvent}
                eventClick={handleEventClick}
                datesSet={handleDatesSet}
                editable={editable}
                views={VIEW_OPTIONS}
                snapDuration={SNAP}
                eventAllow={allowDrop}
                eventDragStart={() => onInteractionChange(true)}
                eventDragStop={() => onInteractionChange(false)}
                eventResizeStart={() => onInteractionChange(true)}
                eventResizeStop={() => onInteractionChange(false)}
                eventDrop={handleChange}
                eventResize={handleChange}
            />
        </div>
    );
}
