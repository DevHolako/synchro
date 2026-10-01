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
import { useTranslation } from '@/i18n/LanguageContext';
import type { TimetableSession } from './types';

interface SessionDetailsDialogProps {
    session: TimetableSession | null;
    onClose: () => void;
}

/** Session times are wall-clock values; reading them as UTC keeps them unshifted. */
function formatDay(value: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: 'full',
        timeZone: 'UTC',
    }).format(new Date(`${value}Z`));
}

const timeOf = (value: string) => value.slice(11, 16);

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
    onClose,
}: SessionDetailsDialogProps) {
    const { t, locale } = useTranslation();

    return (
        <Dialog
            open={session !== null}
            onOpenChange={(open) => !open && onClose()}
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

                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">
                                    {t('timetable.details_close')}
                                </Button>
                            </DialogClose>
                        </DialogFooter>
                    </>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
