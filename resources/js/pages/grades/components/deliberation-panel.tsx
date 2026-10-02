import { router } from '@inertiajs/react';
import { FileDown, Gavel, Hourglass, Undo2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { toastErrors } from '@/lib/toast-errors';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { lock } from '@/routes/exams/deliberation';
import { returnMethod as returnSheet } from '@/routes/exams/grades';
import { pv } from '@/routes/exams';
import { DeliberationLockDialog } from './deliberation-lock-dialog';
import { GradeReturnDialog } from './grade-return-dialog';
import type { Deliberation } from './types';

const RELOADED_PROPS = ['rows', 'sheet', 'can_edit', 'deliberation', 'flash'];

interface DeliberationPanelProps {
    examId: number;
    deliberation: Deliberation;
}

/** The deliberation figures, and the coordinator's decision on a submitted sheet: lock or send back. */
export function DeliberationPanel({
    examId,
    deliberation,
}: DeliberationPanelProps) {
    const { t, locale } = useTranslation();
    const [dialog, setDialog] = useState<'lock' | 'return' | null>(null);
    const [processing, setProcessing] = useState(false);
    const { stats } = deliberation;

    const decide = (url: string, data: Record<string, string>) =>
        router.post(url, data, {
            preserveScroll: true,
            only: RELOADED_PROPS,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDialog(null);
            },
            onError: (errors) => toastErrors(errors),
        });

    const figures = [
        [t('grades.panel_average'), stats.average ?? '—'],
        [t('grades.panel_median'), stats.median ?? '—'],
        [
            t('grades.panel_pass_rate'),
            stats.pass_rate === null ? '—' : `${stats.pass_rate} %`,
        ],
    ];

    return (
        <section className="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 className="font-semibold">{t('grades.panel_title')}</h2>
                    <dl className="mt-2 flex flex-wrap gap-6">
                        {figures.map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-xs text-neutral-500">
                                    {label}
                                </dt>
                                <dd className="text-xl font-bold">{value}</dd>
                            </div>
                        ))}
                    </dl>
                    <p className="mt-1 text-sm text-neutral-500">
                        {t('grades.panel_counts', {
                            passing: stats.passing,
                            failing: stats.failing,
                            absent: stats.absent,
                        })}
                    </p>
                    {deliberation.locked_at && deliberation.locked_by ? (
                        <p className="mt-1 text-sm text-neutral-500">
                            {t('grades.locked_on', {
                                date: `${formatDay(deliberation.locked_at, locale, 'medium')} ${timeOf(deliberation.locked_at)}`,
                                name: deliberation.locked_by,
                            })}
                        </p>
                    ) : null}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {deliberation.pv === 'ready' ? (
                        <Button variant="outline" asChild>
                            <a href={pv.url(examId)}>
                                <FileDown className="mr-1 size-4" />
                                {t('grades.download_pv')}
                            </a>
                        </Button>
                    ) : null}
                    {deliberation.pv === 'pending' ? (
                        <span className="inline-flex items-center gap-1 text-sm text-neutral-500">
                            <Hourglass className="size-4" />
                            {t('grades.pv_pending')}
                        </span>
                    ) : null}
                    {deliberation.can_decide ? (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => setDialog('return')}
                            >
                                <Undo2 className="mr-1 size-4" />
                                {t('grades.return')}
                            </Button>
                            <Button onClick={() => setDialog('lock')}>
                                <Gavel className="mr-1 size-4" />
                                {t('grades.lock')}
                            </Button>
                        </>
                    ) : null}
                </div>
            </div>

            <DeliberationLockDialog
                open={dialog === 'lock'}
                processing={processing}
                onOpenChange={(open) => setDialog(open ? 'lock' : null)}
                onConfirm={() => decide(lock.url(examId), {})}
            />
            <GradeReturnDialog
                open={dialog === 'return'}
                processing={processing}
                onOpenChange={(open) => setDialog(open ? 'return' : null)}
                onConfirm={(reason) =>
                    decide(returnSheet.url(examId), { reason })
                }
            />
        </section>
    );
}
