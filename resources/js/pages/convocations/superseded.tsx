import { Head } from '@inertiajs/react';
import { OctagonAlert } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { useExamsBreadcrumbs } from '@/hooks/use-exams-breadcrumbs';

interface SupersededProps {
    student: string;
    exam: { module: string; start: string; end: string; revision: number };
    /** Where the student now sits; null when they no longer sit the exam. */
    current: { room: string; building: string; seat: number } | null;
}

/** What scanning a convocation replaced by an emergency reschedule shows (ADR 0005). */
export default function SupersededConvocation({
    student,
    exam,
    current,
}: SupersededProps) {
    const { t, locale } = useTranslation();

    useExamsBreadcrumbs();

    return (
        <>
            <Head title={t('convocations.superseded_title')} />

            <div className="mx-auto flex w-full max-w-md flex-col gap-4 p-4">
                <div className="flex gap-3 rounded-lg border-2 border-rose-400 bg-rose-50 p-4 text-rose-900 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-100">
                    <OctagonAlert className="size-7 shrink-0" />
                    <div>
                        <p className="text-lg font-bold uppercase">
                            {t('convocations.superseded_title')}
                        </p>
                        <p className="text-sm">
                            {t('convocations.superseded_desc')}
                        </p>
                    </div>
                </div>

                <div className="rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    <h1 className="text-xl font-bold">{student}</h1>
                    <p className="text-sm text-neutral-500">{exam.module}</p>
                    <p className="mt-2 text-sm font-medium">
                        {t('convocations.superseded_new_time', {
                            when: `${formatDay(exam.start, locale, 'medium')} · ${timeOf(exam.start)}–${timeOf(exam.end)}`,
                            revision: exam.revision,
                        })}
                    </p>
                </div>

                <div className="rounded-lg border border-neutral-200 bg-white p-4 text-center shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    {current ? (
                        <>
                            <div className="text-xs text-neutral-500 uppercase">
                                {t('convocations.superseded_current_room')}
                            </div>
                            <div className="text-2xl font-bold">
                                {current.room}
                            </div>
                            <div className="text-sm text-neutral-500">
                                {current.building}
                            </div>
                            <div className="mt-2 text-lg font-semibold">
                                {t('convocations.seat', { seat: current.seat })}
                            </div>
                        </>
                    ) : (
                        <p className="text-sm">
                            {t('convocations.superseded_not_seated')}
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
