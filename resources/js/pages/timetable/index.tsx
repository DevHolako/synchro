import { Head, router, setLayoutProps, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/i18n/LanguageContext';
import { Permission } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index } from '@/routes/timetable';
import { defaultView } from './components/calendar-utils';
import { prefillFromScope } from './components/schedule/schedule-prefill';
import type { SchedulingOptions } from './components/schedule/types';
import { SyllabusProgressPanel } from './components/syllabus-progress-panel';
import { TimetableCalendar } from './components/timetable-calendar';
import { TimetableEmptyState } from './components/timetable-empty-state';
import { TimetableFilterBar } from './components/timetable-filter-bar';
import { TimetableHeader } from './components/timetable-header';
import { TimetableOverlays } from './components/timetable-overlays';
import { useSessionReschedule } from './components/use-session-reschedule';
import { useTimetableNavigation } from './components/use-timetable-navigation';
import { useTimetablePolling } from './components/use-timetable-polling';
import type {
    ScopePerspective,
    SyllabusProgress,
    TimetableFilters,
    TimetableOptions,
    TimetableScope,
    TimetableSession,
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
    const [interacting, setInteracting] = useState(false);
    const [attendanceSession, setAttendanceSession] =
        useState<TimetableSession | null>(null);
    const reschedule = useSessionReschedule();
    const canOverride = auth.permissions.includes(
        Permission.OverrideSoftConflicts,
    );

    const canRecordAttendance = (session: TimetableSession) =>
        auth.permissions.includes(Permission.RecordAttendance) &&
        (session.teacher.id === auth.user.id ||
            auth.permissions.includes(Permission.ManageSchedules));

    useTimetablePolling(
        scheduling ||
            interacting ||
            reschedule.pending !== null ||
            attendanceSession !== null,
    );

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.timetable'), href: index().url },
            ],
        });
    }, [t]);

    const view = filters.view ?? defaultView(scope.perspective, isMobile);

    const navigation = useTimetableNavigation(filters);

    const handlePerspectiveChange = (perspective: ScopePerspective) => {
        setActiveModuleId(null);
        navigation.changePerspective(perspective);
    };

    const handleSubjectChange = (id: number | null) => {
        setActiveModuleId(null);
        navigation.changeSubject(id);
    };

    const handleOpenScheduling = () => {
        setScheduling(true);

        if (schedulingOptions === undefined) {
            router.reload({ only: ['schedulingOptions'] });
        }
    };

    const handleScheduled = (firstDate: string) => {
        setScheduling(false);
        navigation.goToDate(firstDate);
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
                                canEdit={canSchedule}
                                savingSessionId={
                                    reschedule.pending?.session.id ?? null
                                }
                                onPeriodChange={navigation.goToPeriod}
                                onSessionClick={setSelectedSession}
                                onMove={reschedule.move}
                                onInteractionChange={setInteracting}
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

            <TimetableOverlays
                scheduling={scheduling}
                schedulingOptions={schedulingOptions}
                prefill={prefillFromScope(scope, activeModuleId, filters.date)}
                canSchedule={canSchedule}
                canOverride={canOverride}
                canRecordAttendance={canRecordAttendance}
                reschedule={reschedule}
                selectedSession={selectedSession}
                attendanceSession={attendanceSession}
                onCloseScheduling={() => setScheduling(false)}
                onScheduled={handleScheduled}
                onCloseDetails={() => setSelectedSession(null)}
                onOpenAttendance={setAttendanceSession}
                onCloseAttendance={() => setAttendanceSession(null)}
            />
        </>
    );
}
