import { Head, router, setLayoutProps, usePoll } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { dashboard } from '@/routes';
import {
    destroy as undoCheckIn,
    store as checkIn,
} from '@/routes/exam-candidates/check-in';
import { index as examsIndex } from '@/routes/exams';
import { RoomCheckInRow } from './components/check-in/room-check-in-row';
import type { CheckInRoom, RoomCandidate } from './components/check-in/types';

const POLL_INTERVAL_MS = 10_000;

interface RoomCheckInProps {
    exam: { id: number; module: string; start: string; end: string };
    room: CheckInRoom;
    open: boolean;
    candidates: RoomCandidate[];
}

/** One exam room's candidates, present or not yet arrived; refreshed while invigilators scan. */
export default function RoomCheckIn({
    exam,
    room,
    open,
    candidates,
}: RoomCheckInProps) {
    const { t, locale } = useTranslation();
    const [busy, setBusy] = useState(false);
    const present = candidates.filter(
        (candidate) => candidate.checked_in_at !== null,
    ).length;

    usePoll(POLL_INTERVAL_MS, { only: ['candidates', 'open'] });

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.exams'), href: examsIndex().url },
            ],
        });
    }, [t]);

    const send = useCallback(
        (route: ReturnType<typeof checkIn> | ReturnType<typeof undoCheckIn>) =>
            router.visit(route, {
                preserveScroll: true,
                only: ['candidates', 'open', 'flash'],
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
                onError: (errors) =>
                    Object.values(errors).forEach((message) =>
                        toast.error(message),
                    ),
            }),
        [],
    );
    const handleCheckIn = useCallback(
        (id: number) => send(checkIn(id)),
        [send],
    );
    const handleUndo = useCallback(
        (id: number) => send(undoCheckIn(id)),
        [send],
    );

    return (
        <>
            <Head title={t('check_in.room_title', { room: room.name })} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-bold">
                        {t('check_in.room_title', { room: room.name })}
                    </h1>
                    <p className="text-sm text-neutral-500">
                        {exam.module} ·{' '}
                        {formatDay(exam.start, locale, 'medium')} ·{' '}
                        {timeOf(exam.start)}–{timeOf(exam.end)}
                    </p>
                    {room.first_surname ? (
                        <p className="text-sm text-neutral-500">
                            {t('exams.room_range', {
                                from: room.first_surname,
                                to: room.last_surname ?? '',
                            })}
                        </p>
                    ) : null}
                </div>

                <div className="flex items-baseline justify-between rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                    <span className="text-3xl font-bold">
                        {present} / {candidates.length}
                    </span>
                    <span className="text-sm text-neutral-500">
                        {t(
                            open
                                ? 'check_in.counter_open'
                                : 'check_in.counter_closed',
                        )}
                    </span>
                </div>

                <ul className="divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white dark:divide-neutral-800 dark:border-neutral-800 dark:bg-neutral-900">
                    {candidates.map((candidate) => (
                        <RoomCheckInRow
                            key={candidate.id}
                            candidate={candidate}
                            open={open}
                            busy={busy}
                            onCheckIn={handleCheckIn}
                            onUndo={handleUndo}
                        />
                    ))}
                </ul>
            </div>
        </>
    );
}
