import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { destroy } from '@/routes/course-sessions';
import { hasStarted } from './calendar-utils';
import type { TimetableSession } from './types';
import { useSchoolClock } from './use-school-clock';
import { formatDay, timeOf } from './wall-clock-format';

interface SessionDetailsDialogProps {
    session: TimetableSession | null;
    /** Whether the user may delete sessions that have not started. */
    canDelete: boolean;
    onOpenAttendance: (session: TimetableSession) => void;
    onClose: () => void;
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid grid-cols-[7rem_1fr] gap-2 text-sm">
            <dt className="text-neutral-500 dark:text-neutral-400">{label}</dt>
            <dd className="text-neutral-900 dark:text-neutral-100">{value}</dd>
        </div>
    );
}

export function SessionDetailsDialog({
    session,
    canDelete,
    onOpenAttendance,
    onClose,
}: SessionDetailsDialogProps) {
    const { t, locale } = useTranslation();
    const [confirming, setConfirming] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const schoolNow = useSchoolClock();
    const started = session !== null && hasStarted(session, schoolNow());
    const deletable = canDelete && session !== null && !started;
    const takesAttendance =
        session !== null && started && session.can_take_attendance;

    const handleClose = () => {
        setConfirming(false);
        onClose();
    };

    const handleDelete = () => {
        if (!session) {
            return;
        }

        router.delete(destroy.url(session.id), {
            preserveScroll: true,
            preserveState: true,
            only: ['sessions', 'syllabus', 'flash'],
            onStart: () => setDeleting(true),
            onFinish: () => setDeleting(false),
            onSuccess: handleClose,
            onError: (errors) => toast.error(Object.values(errors)[0]),
        });
    };

    return (
        <Dialog
            open={session !== null}
            onOpenChange={(open) => !open && handleClose()}
        >
            <DialogContent className="sm:max-w-md">
                {session ? (
                    <>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <span
                                    className="size-3 shrink-0 rounded-full"
                                    style={{
                                        backgroundColor:
                                            session.module.color_code,
                                    }}
                                />
                                {session.module.code} · {session.module.name}
                            </DialogTitle>
                            <DialogDescription>
                                {formatDay(session.start, locale)}
                            </DialogDescription>
                        </DialogHeader>

                        <dl className="flex flex-col gap-2">
                            <DetailRow
                                label={t('timetable.details_time')}
                                value={`${timeOf(session.start)} – ${timeOf(session.end)}`}
                            />
                            <DetailRow
                                label={t('timetable.details_teacher')}
                                value={session.teacher.name}
                            />
                            <DetailRow
                                label={t('timetable.details_room')}
                                value={`${session.room.name} · ${session.room.building}`}
                            />
                            <DetailRow
                                label={t('timetable.details_groups')}
                                value={session.groups
                                    .map((group) => group.name)
                                    .join(', ')}
                            />
                        </dl>

                        {session.overrides.length > 0 ? (
                            <div className="flex flex-col gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/40">
                                <p className="font-medium text-amber-900 dark:text-amber-200">
                                    {t('timetable.details_overrides')}
                                </p>
                                {session.overrides.map((override) => (
                                    <div key={override.id}>
                                        <p className="font-medium text-amber-900 dark:text-amber-200">
                                            {t(
                                                `timetable.override_type_${override.type}`,
                                            )}
                                        </p>
                                        <p className="text-amber-800 dark:text-amber-300">
                                            {override.justification}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        ) : null}

                        {confirming ? (
                            <p className="text-sm font-medium text-red-700 dark:text-red-400">
                                {t('timetable.delete_confirm')}
                            </p>
                        ) : null}

                        <DialogFooter className="gap-2">
                            {deletable && confirming ? (
                                <>
                                    <Button
                                        variant="outline"
                                        onClick={() => setConfirming(false)}
                                        disabled={deleting}
                                    >
                                        {t('timetable.delete_cancel')}
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={handleDelete}
                                        disabled={deleting}
                                    >
                                        {deleting ? <Spinner /> : null}
                                        {t('timetable.delete_confirm_button')}
                                    </Button>
                                </>
                            ) : (
                                <>
                                    {takesAttendance ? (
                                        <Button
                                            onClick={() => {
                                                onOpenAttendance(session);
                                                handleClose();
                                            }}
                                        >
                                            {t('attendance.open_button')}
                                        </Button>
                                    ) : null}
                                    {deletable ? (
                                        <Button
                                            variant="outline"
                                            className="text-red-700 dark:text-red-400"
                                            onClick={() => setConfirming(true)}
                                        >
                                            {t('timetable.delete_button')}
                                        </Button>
                                    ) : null}
                                    <DialogClose asChild>
                                        <Button variant="outline">
                                            {t('timetable.details_close')}
                                        </Button>
                                    </DialogClose>
                                </>
                            )}
                        </DialogFooter>
                    </>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
