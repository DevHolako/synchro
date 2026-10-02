import type { EventInput } from '@fullcalendar/core';
import type {
    ScopePerspective,
    TimetableSession,
    TimetableView,
} from './types';

const DARK_TEXT = '#111827';
const LIGHT_TEXT = '#ffffff';
const HEX_COLOR = /^#?([0-9a-f]{6})$/i;
/** Relative luminance above which dark text reads better than white (WCAG midpoint). */
const LUMINANCE_THRESHOLD = 0.179;

function channelLuminance(channel: number): number {
    const value = channel / 255;

    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
}

/**
 * Dark or white text, whichever contrasts more with the module colour.
 */
export function readableTextColor(background: string): string {
    const match = HEX_COLOR.exec(background);

    if (!match) {
        return DARK_TEXT;
    }

    const hex = Number.parseInt(match[1], 16);
    const luminance =
        0.2126 * channelLuminance((hex >> 16) & 0xff) +
        0.7152 * channelLuminance((hex >> 8) & 0xff) +
        0.0722 * channelLuminance(hex & 0xff);

    return luminance > LUMINANCE_THRESHOLD ? DARK_TEXT : LIGHT_TEXT;
}

const pad = (value: number) => String(value).padStart(2, '0');

/**
 * The browser's local time as an offset-less ISO string.
 *
 * The calendar runs in UTC so wall-clock session times are never shifted; feeding it the
 * local wall clock the same way keeps "today" and the now-indicator right.
 */
export function wallClockNow(): string {
    const now = new Date();

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

/**
 * The view to open when the URL names none: a list on phones and for a whole campus.
 */
export function defaultView(
    perspective: ScopePerspective,
    isMobile: boolean,
): TimetableView {
    return isMobile || perspective === 'campus' ? 'listWeek' : 'timeGridWeek';
}

/** Whether a session has begun; wall-clock ISO strings compare correctly as text. */
export function hasStarted(session: TimetableSession, now: string): boolean {
    return session.start <= now;
}

/** A calendar date (UTC-coerced wall clock) in the server's `Y-m-d H:i` format. */
export function toWallClock(date: Date): string {
    return date.toISOString().slice(0, 16).replace('T', ' ');
}

interface EventState {
    dimmed: boolean;
    editable: boolean;
    saving: boolean;
}

export function toCalendarEvent(
    session: TimetableSession,
    { dimmed, editable, saving }: EventState,
): EventInput {
    const color = session.module.color_code;

    return {
        id: String(session.id),
        title: session.module.code,
        start: session.start,
        end: session.end,
        backgroundColor: color,
        borderColor: color,
        textColor: readableTextColor(color),
        classNames: [
            ...(dimmed ? ['opacity-30'] : []),
            ...(saving ? ['opacity-50', 'animate-pulse'] : []),
        ],
        editable,
        extendedProps: { session },
    };
}
