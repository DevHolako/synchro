import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { lazy, Suspense, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index } from '@/routes/exams';
import { ExamFilterBar } from './components/exam-filter-bar';
import { ExamOverlays } from './components/exam-overlays';
import { ExamPeriodBar } from './components/exam-period-bar';
import { ExamStatsCards } from './components/exam-stats';
import { ExamTable } from './components/exam-table';
import type {
    Exam,
    ExamFilters,
    ExamListView,
    ExamOptions,
    ExamPeriod,
    ExamStats,
} from './components/types';
import { useExamOverlays } from './components/use-exam-overlays';

const ExamsCalendar = lazy(() => import('./components/exams-calendar'));

const DEFAULT_FILTERS: ExamFilters = {
    state: '',
    program_id: '',
    group_id: '',
};

interface ExamsIndexProps {
    periods: ExamPeriod[];
    periodId: number | null;
    exams: Exam[];
    stats: ExamStats | null;
    filters: ExamFilters;
    canManage: boolean;
    options: ExamOptions | null;
}

export default function ExamsIndex({
    periods,
    periodId,
    exams,
    stats,
    filters: initialFilters,
    canManage,
    options,
}: ExamsIndexProps) {
    const { t } = useTranslation();
    const [filters, setFilters] = useState<ExamFilters>(initialFilters);
    const [view, setView] = useState<ExamListView>('list');
    const overlays = useExamOverlays();
    const period = periods.find((item) => item.id === periodId) ?? null;

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.exams'), href: index().url },
            ],
        });
    }, [t]);

    const visit = (id: number | null, next: ExamFilters) =>
        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries({ period: id ?? '', ...next }).filter(
                    ([, value]) => value !== '',
                ),
            ),
            { preserveState: true },
        );

    const handleReset = () => {
        setFilters(DEFAULT_FILTERS);
        visit(periodId, DEFAULT_FILTERS);
    };

    const handleCalendarClick = (exam: Exam) => {
        if (
            canManage &&
            (exam.state === 'draft' || exam.state === 'scheduled')
        ) {
            overlays.editExam(exam);
        }
    };

    return (
        <>
            <Head title={t('exams.title')} />

            <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {t(canManage ? 'exams.title' : 'exams.title_mine')}
                        </h1>
                        <p className="max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">
                            {t(
                                canManage
                                    ? 'exams.description'
                                    : 'exams.description_mine',
                            )}
                        </p>
                    </div>
                    {canManage && period ? (
                        <Button onClick={overlays.createExam}>
                            <Plus className="mr-1.5 size-4" />
                            {t('exams.new_exam')}
                        </Button>
                    ) : null}
                </div>

                <ExamPeriodBar
                    periods={periods}
                    period={period}
                    canManage={canManage}
                    onSelect={(id) => {
                        setFilters(DEFAULT_FILTERS);
                        visit(id, DEFAULT_FILTERS);
                    }}
                    onCreate={overlays.createPeriod}
                    onEdit={overlays.editPeriod}
                    onConfirm={overlays.confirm}
                />

                {period && stats ? (
                    <>
                        <ExamStatsCards stats={stats} />
                        <ExamFilterBar
                            filters={filters}
                            options={options}
                            view={view}
                            onChange={(patch) =>
                                setFilters((current) => ({
                                    ...current,
                                    ...patch,
                                }))
                            }
                            onApply={() => visit(periodId, filters)}
                            onReset={handleReset}
                            onViewChange={setView}
                        />
                        {view === 'calendar' ? (
                            <Suspense
                                fallback={<Skeleton className="h-96 w-full" />}
                            >
                                <ExamsCalendar
                                    period={period}
                                    exams={exams}
                                    onExamClick={handleCalendarClick}
                                />
                            </Suspense>
                        ) : (
                            <ExamTable
                                exams={exams}
                                canManage={canManage}
                                onEdit={overlays.editExam}
                                onAllocate={overlays.allocate}
                                onConfirm={overlays.confirm}
                            />
                        )}
                    </>
                ) : (
                    <p className="rounded-lg border border-dashed border-neutral-300 p-12 text-center text-sm text-neutral-500 dark:border-neutral-700">
                        {t(
                            canManage
                                ? 'exams.no_period_manage'
                                : 'exams.no_period',
                        )}
                    </p>
                )}
            </div>

            <ExamOverlays
                overlays={overlays}
                period={period}
                options={options}
            />
        </>
    );
}
