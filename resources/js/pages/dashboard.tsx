import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { DashboardExamTimeline } from './dashboard/components/dashboard-exam-timeline';
import { DashboardNotificationsCard } from './dashboard/components/dashboard-notifications-card';
import { DashboardQuickActions } from './dashboard/components/dashboard-quick-actions';
import { DashboardScheduleTimeline } from './dashboard/components/dashboard-schedule-timeline';
import { DashboardStats } from './dashboard/components/dashboard-stats';
import type { DashboardPageProps } from './dashboard/components/types';

export default function Dashboard({
    stats,
    upcomingSessions,
    upcomingExams,
    recentNotifications,
    permissions,
}: DashboardPageProps) {
    const { t, locale } = useTranslation();
    const { auth } = usePage().props;

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [{ title: t('nav.dashboard'), href: dashboard().url }],
        });
    }, [t]);

    const formattedToday = new Date().toLocaleDateString(
        locale === 'fr' ? 'fr-FR' : 'en-US',
        { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' },
    );

    return (
        <>
            <Head title={t('nav.dashboard')} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Welcome Header */}
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                            {t('dashboard.welcome_title')}, {auth.user?.name}
                        </h1>
                        <p className="text-xs text-muted-foreground sm:text-sm">
                            {t('dashboard.welcome_subtitle')}
                        </p>
                    </div>
                    <div className="text-xs font-medium capitalize text-muted-foreground sm:text-sm">
                        {formattedToday}
                    </div>
                </div>

                {/* KPI Metrics */}
                <DashboardStats stats={stats} />

                {/* Main Content Layout */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    {/* Left Column: Schedules & Exams */}
                    <div className="space-y-6 lg:col-span-7">
                        {permissions.canViewSchedules && (
                            <DashboardScheduleTimeline sessions={upcomingSessions} />
                        )}
                        {permissions.canViewExams && (
                            <DashboardExamTimeline exams={upcomingExams} />
                        )}
                    </div>

                    {/* Right Column: Quick Actions & Notifications */}
                    <div className="space-y-6 lg:col-span-5">
                        <DashboardQuickActions permissions={permissions} />
                        <DashboardNotificationsCard notifications={recentNotifications} />
                    </div>
                </div>
            </div>
        </>
    );
}
