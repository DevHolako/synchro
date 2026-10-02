import { CheckCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import type { TimetableSession } from '../types';
import { AttendanceRow } from './attendance-row';
import { ATTENDANCE_STATUSES } from './types';
import type { AttendanceStatus } from './types';
import { useAttendanceRegister } from './use-attendance-register';

interface AttendanceSheetProps {
    session: TimetableSession;
    onClose: () => void;
}

/** A session's attendance register: one row per student, saved in one go. */
export function AttendanceSheet({ session, onClose }: AttendanceSheetProps) {
    const { t } = useTranslation();
    const register = useAttendanceRegister(session.id, onClose);

    const marks = Object.values(register.draft);
    const countOf = (status: AttendanceStatus | null) =>
        marks.filter((mark) => mark.status === status).length;

    return (
        <Sheet open onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="flex w-full flex-col gap-0 sm:max-w-lg">
                <SheetHeader>
                    <SheetTitle>{t('attendance.title')}</SheetTitle>
                    <SheetDescription>
                        {session.module.code} · {session.module.name} ·{' '}
                        {session.start.slice(0, 10)}{' '}
                        {session.start.slice(11, 16)}–
                        {session.end.slice(11, 16)}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 border-y border-neutral-200 px-4 py-2 text-xs dark:border-neutral-800">
                    {ATTENDANCE_STATUSES.map((status) => (
                        <span key={status}>
                            {t(`attendance.count_${status}`)}:{' '}
                            <strong>{countOf(status)}</strong>
                        </span>
                    ))}
                    <span className="text-neutral-500">
                        {t('attendance.count_unmarked')}:{' '}
                        <strong>{countOf(null)}</strong>
                    </span>
                </div>

                <div className="flex-1 overflow-y-auto px-4">
                    {register.students === null ? (
                        <p className="flex items-center gap-2 py-8 text-sm text-neutral-500">
                            <Spinner />
                            {t('attendance.loading')}
                        </p>
                    ) : null}
                    {register.students?.length === 0 ? (
                        <p className="py-8 text-sm text-neutral-500">
                            {t('attendance.empty')}
                        </p>
                    ) : null}
                    <ul className="divide-y divide-neutral-100 dark:divide-neutral-800">
                        {register.students?.map((student) => (
                            <AttendanceRow
                                key={student.student_id}
                                student={student}
                                status={
                                    register.draft[student.student_id]
                                        ?.status ?? null
                                }
                                remarks={
                                    register.draft[student.student_id]
                                        ?.remarks ?? ''
                                }
                                onStatus={register.setStatus}
                                onRemarks={register.setRemarks}
                            />
                        ))}
                    </ul>
                </div>

                <SheetFooter className="flex-row justify-between gap-2 border-t border-neutral-200 dark:border-neutral-800">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={register.markAllPresent}
                        disabled={!register.students?.length}
                    >
                        <CheckCheck className="size-4" />
                        {t('attendance.mark_all_present')}
                    </Button>
                    <Button
                        type="button"
                        onClick={register.save}
                        disabled={register.saving || !register.students?.length}
                    >
                        {register.saving ? <Spinner /> : null}
                        {t('attendance.save')}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
