import { Head, router, setLayoutProps, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/i18n/LanguageContext';
import { Permission } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index } from '@/routes/timetable';
import { defaultView } from './components/calendar-utils';
import { ScheduleSessionsDialog } from './components/schedule/schedule-sessions-dialog';
import type { SchedulingOptions } from './components/schedule/types';
import { SessionDetailsDialog } from './components/session-details-dialog';
import { SyllabusProgressPanel } from './components/syllabus-progress-panel';
import { TimetableCalendar } from './components/timetable-calendar';
import { TimetableEmptyState } from './components/timetable-empty-state';
import { TimetableFilterBar } from './components/timetable-filter-bar';
import { TimetableHeader } from './components/timetable-header';
import type {
    ScopePerspective,
    SyllabusProgress,
    TimetableFilters,
    TimetableOptions,
    TimetableScope,
    TimetableSession,
    TimetableView,
} from './components/types';

interface TimetableIndexProps {
    sessions: TimetableSession[];
    filters: TimetableFilters;
    scope: TimetableScope;
    canBrowse: boolean;
    options: TimetableOptions | null;
    syllabus: SyllabusProgress[] | null;
    noGroup: boolean;
    canSchedule: boolean;
    /** Absent until the scheduling wizard first asks for it. */
    schedulingOptions?: SchedulingOptions | null;
}

/** Moving to another period only needs that period's sessions. */
const PERIOD_PROPS = ['sessions', 'filters'];

function toQuery(filters: TimetableFilters) {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== null),
    );
}

export default function TimetableIndex({
    sessions,
    filters,
    scope,
    canBrowse,
    options,
    syllabus,
    noGroup,
    canSchedule,
    schedulingOptions,
}: TimetableIndexProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const isMobile = useIsMobile();
    const [selectedSession, setSelectedSession] =
        useState<TimetableSession | null>(null);
    const [activeModuleId, setActiveModuleId] = useState<number | null>(null);
    const [scheduling, setScheduling] = useState(false);

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.timetable'), href: index().url },
            ],
        });
    }, [t]);

    const view = filters.view ?? defaultView(scope.perspective, isMobile);

    const visit = (next: TimetableFilters, only?: string[]) =>
        router.get(index.url(), toQuery(next), {
            preserveState: true,
            preserveScroll: true,
            ...(only ? { only, replace: true } : {}),
        });

    const handlePeriodChange = (date: string, nextView: TimetableView) =>
        visit({ ...filters, date, view: nextView }, PERIOD_PROPS);

    const handlePerspectiveChange = (perspective: ScopePerspective) => {
        setActiveModuleId(null);
        visit({ ...filters, perspective, id: null });
    };

    const handleSubjectChange = (id: number | null) => {
        setActiveModuleId(null);
        visit({ ...filters, id });
    };

    const handleOpenScheduling = () => {
        setScheduling(true);

        if (schedulingOptions === undefined) {
            router.reload({ only: ['schedulingOptions'] });
        }
    };

    const handleScheduled = (firstDate: string) => {
        setScheduling(false);
        visit({ ...filters, date: firstDate });
    };

    const handleToggleModule = useCallback(
        (moduleId: number) =>
            setActiveModuleId((current) =>
                current === moduleId ? null : moduleId,
            ),
        [],
    );

    return (
        <>
            <Head title={t('timetable.title')} />

            <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <TimetableHeader
                    canBrowse={canBrowse}
                    canSchedule={canSchedule}
                    onSchedule={handleOpenScheduling}
                />

                {canBrowse && options ? (
                    <TimetableFilterBar
                        perspective={scope.perspective}
                        subjectId={scope.id}
                        options={options}
                        onPerspectiveChange={handlePerspectiveChange}
                        onSubjectChange={handleSubjectChange}
                    />
                ) : null}

                {noGroup ? (
                    <TimetableEmptyState
                        title={t('timetable.no_group_title')}
                        description={t('timetable.no_group_desc')}
                    />
                ) : scope.id === null ? (
                    <TimetableEmptyState
                        title={t('timetable.empty_pick_title')}
                        description={t('timetable.empty_pick_desc')}
                    />
                ) : (
                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div className={syllabus ? '' : 'lg:col-span-2'}>
                            <TimetableCalendar
                                // FullCalendar only reads its initial view and date: remount when they
                                // change outside it (another subject, or the phone breakpoint).
                                key={`${scope.perspective}-${scope.id}-${view}`}
                                sessions={sessions}
                                perspective={scope.perspective}
                                date={filters.date}
                                view={view}
                                activeModuleId={activeModuleId}
                                onPeriodChange={handlePeriodChange}
                                onSessionClick={setSelectedSession}
                            />
                        </div>
                        {syllabus ? (
                            <SyllabusProgressPanel
                                modules={syllabus}
                                activeModuleId={activeModuleId}
                                onToggleModule={handleToggleModule}
                            />
                        ) : null}
                    </div>
                )}
            </div>

            {scheduling ? (
                <ScheduleSessionsDialog
                    options={schedulingOptions}
                    prefill={{
                        moduleId: activeModuleId,
                        groupId:
                            scope.perspective === 'group' ? scope.id : null,
                        teacherId:
                            scope.perspective === 'teacher' ? scope.id : null,
                        roomId: scope.perspective === 'room' ? scope.id : null,
                        date: filters.date,
                    }}
                    canOverride={auth.permissions.includes(
                        Permission.OverrideSoftConflicts,
                    )}
                    onClose={() => setScheduling(false)}
                    onScheduled={handleScheduled}
                />
            ) : null}

            <SessionDetailsDialog
                session={selectedSession}
                onClose={() => setSelectedSession(null)}
            />
        </>
    );
}
