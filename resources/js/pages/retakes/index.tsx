import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index as retakesIndex } from '@/routes/retakes';
import { RetakeExamDialog } from './components/retake-exam-dialog';
import { RetakeFilterBar } from './components/retake-filter-bar';
import { RetakeModuleCard } from './components/retake-module-card';
import type {
    RetakeModule,
    RetakePeriod,
    RetakePeriodOption,
} from './components/types';

interface RetakesIndexProps {
    periods: RetakePeriodOption[];
    period: RetakePeriod | null;
    modules: RetakeModule[];
}

/** Who failed each module in the year's normal sessions, and their retake exams. */
export default function RetakesIndex({
    periods,
    period,
    modules,
}: RetakesIndexProps) {
    const { t } = useTranslation();
    const [creating, setCreating] = useState<RetakeModule | null>(null);
    const handleClose = useCallback(() => setCreating(null), []);
    const handlePeriodChange = useCallback(
        (periodId: number) =>
            router.get(
                retakesIndex().url,
                { period: periodId },
                { preserveState: true },
            ),
        [],
    );

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.retakes'), href: retakesIndex().url },
            ],
        });
    }, [t]);

    return (
        <>
            <Head title={t('retakes.title')} />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        {t('retakes.title')}
                    </h1>
                    <p className="text-sm text-neutral-500">
                        {t('retakes.description')}
                    </p>
                </div>

                {period === null ? (
                    <p className="text-sm text-neutral-500">
                        {t('retakes.no_period')}
                    </p>
                ) : (
                    <>
                        <RetakeFilterBar
                            periods={periods}
                            periodId={period.id}
                            onPeriodChange={handlePeriodChange}
                        />

                        {modules.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                                {t('retakes.empty')}
                            </div>
                        ) : (
                            modules.map((module) => (
                                <RetakeModuleCard
                                    key={module.module_id}
                                    module={module}
                                    periodId={period.id}
                                    onCreate={setCreating}
                                />
                            ))
                        )}

                        <RetakeExamDialog
                            key={creating?.module_id ?? 'closed'}
                            period={period}
                            module={creating}
                            onClose={handleClose}
                        />
                    </>
                )}
            </div>
        </>
    );
}
