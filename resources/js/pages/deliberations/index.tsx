import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index as deliberationsIndex } from '@/routes/deliberations';
import { DeliberationFilterBar } from './components/deliberation-filter-bar';
import { DeliberationStats } from './components/deliberation-stats';
import { DeliberationTable } from './components/deliberation-table';
import type {
    DeliberationPeriod,
    DeliberationSheet,
    DeliberationStats as Stats,
} from './components/types';

interface DeliberationsIndexProps {
    periods: DeliberationPeriod[];
    period_id: number | null;
    stats: Stats;
    sheets: DeliberationSheet[];
    filters: { status: string };
}

/** The coordinators' board: each finished exam's grade sheet, those to deliberate first. */
export default function DeliberationsIndex({
    periods,
    period_id: periodId,
    stats,
    sheets,
    filters,
}: DeliberationsIndexProps) {
    const { t } = useTranslation();

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                {
                    title: t('nav.deliberations'),
                    href: deliberationsIndex().url,
                },
            ],
        });
    }, [t]);

    const handleFilters = useCallback(
        (next: { period?: number; status?: string }) =>
            router.get(
                deliberationsIndex().url,
                { period: next.period, status: next.status || undefined },
                { preserveState: true, preserveScroll: true },
            ),
        [],
    );

    return (
        <>
            <Head title={t('deliberations.title')} />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        {t('deliberations.title')}
                    </h1>
                    <p className="text-sm text-neutral-500">
                        {t('deliberations.description')}
                    </p>
                </div>

                {periods.length === 0 ? (
                    <p className="text-sm text-neutral-500">
                        {t('deliberations.no_period')}
                    </p>
                ) : (
                    <>
                        <DeliberationFilterBar
                            periods={periods}
                            periodId={periodId}
                            status={filters.status}
                            onChange={handleFilters}
                        />
                        <DeliberationStats stats={stats} />
                        <DeliberationTable sheets={sheets} />
                    </>
                )}
            </div>
        </>
    );
}
