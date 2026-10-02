import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { dashboard } from '@/routes';
import { index as retakesIndex } from '@/routes/retakes';
import { RetakeExamDialog } from './components/retake-exam-dialog';
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
                        <select
                            aria-label={t('retakes.period')}
                            className={`${FIELD_CLASS} sm:max-w-sm`}
                            value={period.id}
                            onChange={(event) =>
                                router.get(
                                    retakesIndex().url,
                                    { period: event.target.value },
                                    { preserveState: true },
                                )
                            }
                        >
                            {periods.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name} · {option.academic_year}
                                </option>
                            ))}
                        </select>

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
