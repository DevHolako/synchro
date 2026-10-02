import { router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from '@/i18n/LanguageContext';
import { reschedule } from '@/routes/course-sessions';
import type { SlotConflict } from './schedule/types';
import type { TimetableSession } from './types';

/** A drop or resize waiting for the server, or for the user to confirm a soft conflict. */
export interface PendingMove {
    session: TimetableSession;
    startsAt: string;
    endsAt: string;
    /** Puts the event back where it was. */
    revert: () => void;
    /** Set when the server asked for an override (409). */
    softConflicts?: SlotConflict[];
}

type RescheduleForm = {
    starts_at: string;
    ends_at: string;
    force_override?: boolean;
    justification?: string;
};

const SOFT_CONFLICT_STATUS = 409;

/**
 * Saves calendar drops straight away: the server refuses hard conflicts (422, the event
 * snaps back) and asks for an override on soft ones (409, nothing written yet).
 */
export function useSessionReschedule() {
    const { t } = useTranslation();
    const http = useHttp<RescheduleForm>({ starts_at: '', ends_at: '' });
    const [pending, setPending] = useState<PendingMove | null>(null);

    const refuse = (move: PendingMove, description?: string) => {
        move.revert();
        setPending(null);
        toast.error(t('timetable.reschedule_refused'), { description });
    };

    const send = (move: PendingMove, justification?: string) => {
        http.transform(() => ({
            starts_at: move.startsAt,
            ends_at: move.endsAt,
            ...(justification ? { force_override: true, justification } : {}),
        }));
        http.patch(reschedule.url(move.session.id), {
            onSuccess: () => {
                setPending(null);
                toast.success(t('timetable.rescheduled'));
                router.reload({ only: ['sessions', 'syllabus'] });
            },
            onError: (errors) => refuse(move, Object.values(errors)[0]),
            onHttpException: (response) => {
                if (response.status === SOFT_CONFLICT_STATUS) {
                    const body = JSON.parse(response.data) as {
                        soft_conflicts: SlotConflict[];
                    };
                    setPending({ ...move, softConflicts: body.soft_conflicts });
                } else {
                    refuse(move, t('timetable.reschedule_failed'));
                }
            },
            onNetworkError: () =>
                refuse(move, t('timetable.reschedule_failed')),
        }).catch(() => undefined);
    };

    return {
        pending,
        saving: http.processing,
        move: (move: PendingMove) => {
            setPending(move);
            send(move);
        },
        confirm: (justification: string) =>
            pending && send(pending, justification),
        cancel: () => {
            pending?.revert();
            setPending(null);
        },
    };
}
