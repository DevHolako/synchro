import {
    Award,
    Bell,
    Building2,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    Clock,
    Users,
} from 'lucide-react';
import React, { memo } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import type { DashboardStats as DashboardStatsType } from './types';

interface DashboardStatsProps {
    stats: DashboardStatsType;
}

export const DashboardStats = memo(function DashboardStats({ stats }: DashboardStatsProps) {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {stats.sessions_this_week !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                            <CalendarDays className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_sessions_this_week')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.sessions_this_week}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_sessions_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {stats.upcoming_exams !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                            <Award className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_upcoming_exams')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.upcoming_exams}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_upcoming_exams_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {stats.active_rooms !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <Building2 className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_active_rooms')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.active_rooms}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_active_rooms_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {stats.active_groups !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                            <Users className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_active_groups')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.active_groups}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_active_groups_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {stats.pending_unavailabilities !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                            <Clock className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_pending_unavailabilities')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.pending_unavailabilities}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_pending_unavailabilities_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {stats.my_unavailabilities !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                            <CalendarClock className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_my_unavailabilities')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.my_unavailabilities}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_my_unavailabilities_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            {stats.my_grades_count !== undefined && (
                <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                    <CardContent className="flex items-center gap-4 p-5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400">
                            <CheckCircle2 className="size-5.5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('dashboard.stats_grades_count')}
                            </p>
                            <div className="mt-0.5 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-foreground">
                                    {stats.my_grades_count}
                                </span>
                            </div>
                            <p className="truncate text-xs text-muted-foreground/80">
                                {t('dashboard.stats_grades_desc')}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            )}

            <Card className="relative overflow-hidden border-border/60 bg-card/60 backdrop-blur-xs transition-shadow hover:shadow-xs">
                <CardContent className="flex items-center gap-4 p-5">
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                        <Bell className="size-5.5" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs font-medium text-muted-foreground">
                            {t('dashboard.stats_notifications')}
                        </p>
                        <div className="mt-0.5 flex items-baseline gap-2">
                            <span className="text-2xl font-bold tracking-tight text-foreground">
                                {stats.unread_notifications}
                            </span>
                        </div>
                        <p className="truncate text-xs text-muted-foreground/80">
                            {t('dashboard.stats_notifications_desc')}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
});
