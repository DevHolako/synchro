import { usePoll } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import type { ImportStatus, SpreadsheetImport } from './types';
import { ACTIVE_IMPORT_STATUSES } from './types';

const POLL_INTERVAL_MS = 2000;

/**
 * Reloads the `imports` prop while any import is queued or processing, and
 * reports each import that finishes during this visit.
 */
export function useImportPolling(
    imports: SpreadsheetImport[],
    onFinished: (record: SpreadsheetImport) => void,
): void {
    const { start, stop } = usePoll(
        POLL_INTERVAL_MS,
        { only: ['imports'] },
        { autoStart: false },
    );
    const previous = useRef<Map<number, ImportStatus>>(
        new Map(imports.map((record) => [record.id, record.status])),
    );
    const hasActive = imports.some((record) =>
        ACTIVE_IMPORT_STATUSES.includes(record.status),
    );

    useEffect(() => {
        if (hasActive) {
            start();
        } else {
            stop();
        }
    }, [hasActive, start, stop]);

    useEffect(() => {
        for (const record of imports) {
            const before = previous.current.get(record.id);

            if (
                before &&
                ACTIVE_IMPORT_STATUSES.includes(before) &&
                !ACTIVE_IMPORT_STATUSES.includes(record.status)
            ) {
                onFinished(record);
            }
        }

        previous.current = new Map(
            imports.map((record) => [record.id, record.status]),
        );
    }, [imports, onFinished]);
}
