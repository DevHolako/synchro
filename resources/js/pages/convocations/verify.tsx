import { Head, setLayoutProps } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';
import { useEffect } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { dashboard } from '@/routes';
import { index as examsIndex } from '@/routes/exams';

interface ConvocationVerifyProps {
    candidate: {
        name: string;
        student_number: string | null;
        group: string | null;
        room: string;
        building: string;
        seat: number;
    };
    exam: { module: string; start: string; end: string; state: string };
}

/** What a scanned convocation shows the invigilator: who the candidate is and where they sit. */
export default function ConvocationVerify({
    candidate,
    exam,
}: ConvocationVerifyProps) {
    const { t, locale } = useTranslation();

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.exams'), href: examsIndex().url },
            ],
        });
    }, [t]);

    const facts = [
        [t('convocations.student_number'), candidate.student_number ?? '—'],
        [t('convocations.group'), candidate.group ?? '—'],
        [t('convocations.exam'), exam.module],
        [
            t('convocations.when'),
            `${formatDay(exam.start, locale, 'medium')} · ${timeOf(exam.start)}–${timeOf(exam.end)}`,
        ],
    ];

    return (
        <>
            <Head title={t('convocations.title')} />

            <div className="mx-auto flex w-full max-w-md flex-col gap-4 p-4">
                <div className="flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                    <BadgeCheck className="size-5 shrink-0" />
                    <span className="font-semibold">
                        {t('convocations.valid')}
                    </span>
                </div>

                <div className="rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    <h1 className="text-xl font-bold">{candidate.name}</h1>
                    <dl className="mt-3 grid gap-2 text-sm">
                        {facts.map(([label, value]) => (
                            <div
                                key={label}
                                className="flex justify-between gap-3"
                            >
                                <dt className="text-neutral-500">{label}</dt>
                                <dd className="text-right font-medium">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>

                <div className="rounded-lg border border-neutral-200 bg-white p-4 text-center shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="text-xs text-neutral-500 uppercase">
                        {t('convocations.room')}
                    </div>
                    <div className="text-2xl font-bold">{candidate.room}</div>
                    <div className="text-sm text-neutral-500">
                        {candidate.building}
                    </div>
                    <div className="mt-2 text-lg font-semibold">
                        {t('convocations.seat', { seat: candidate.seat })}
                    </div>
                </div>
            </div>
        </>
    );
}
