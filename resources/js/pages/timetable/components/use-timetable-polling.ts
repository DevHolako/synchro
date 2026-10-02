import { usePoll } from '@inertiajs/react';
import { useEffect } from 'react';

/** How often an open timetable picks up other people's changes. */
const POLL_INTERVAL_MS = 30_000;

/**
 * Reloads the sessions and syllabus panel every 30 seconds, paused while the user is
 * editing so their own pending change is not redrawn under them. Inertia slows polling
 * down in background tabs. (Reverb could replace this later: see the handoff.)
 */
export function useTimetablePolling(paused: boolean): void {
    const { start, stop } = usePoll(
        POLL_INTERVAL_MS,
        { only: ['sessions', 'syllabus'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (paused) {
            stop();
        } else {
            start();
        }

        return stop;
    }, [paused, start, stop]);
}
