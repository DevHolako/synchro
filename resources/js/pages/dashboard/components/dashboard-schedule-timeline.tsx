import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, Clock, MapPin, User, Users } from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import { index as timetableIndex } from '@/routes/timetable';
import type { DashboardSessionItem } from './types';

interface DashboardScheduleTimelineProps {
    sessions: DashboardSessionItem[];
}

function formatSessionTime(startsAt: string, endsAt: string): string {
    const start = new Date(startsAt);
    const end = new Date(endsAt);
    const startStr = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const endStr = end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return `${startStr} – ${endStr}`;
}

function formatSessionDate(dateStr: string, locale: string): string {
    const date = new Date(dateStr);
    const today = new Date();
    const isToday =
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear();

    if (isToday) {
        return locale === 'fr' ? "Aujourd'hui" : 'Today';
    }

    return date.toLocaleDateString(locale === 'fr' ? 'fr-FR' : 'en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

export const DashboardScheduleTimeline = memo(function DashboardScheduleTimeline({
    sessions,
}: DashboardScheduleTimelineProps) {
    const { t, locale } = useTranslation();

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-xs">
            <CardHeader className="flex flex-row items-center justify-between pb-3">
                <div className="space-y-1">
                    <CardTitle className="text-base font-semibold">
                        {t('dashboard.upcoming_sessions_title')}
                    </CardTitle>
                    <CardDescription className="text-xs">
                        {t('dashboard.upcoming_sessions_subtitle')}
                    </CardDescription>
                </div>
                <Button variant="ghost" size="sm" asChild className="gap-1 text-xs">
                    <Link href={timetableIndex()}>
                        {t('dashboard.upcoming_sessions_view_all')}
                        <ArrowRight className="size-3.5" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                {sessions.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <CalendarDays className="size-10 text-muted-foreground/40" />
                        <p className="mt-3 text-sm font-medium text-muted-foreground">
                            {t('dashboard.upcoming_sessions_empty')}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {sessions.map((session, index) => {
                            const dateLabel = formatSessionDate(session.starts_at, locale);
                            const timeLabel = formatSessionTime(session.starts_at, session.ends_at);
                            const isFirst = index === 0;

                            return (
                                <div
                                    key={session.id}
                                    className="group relative flex flex-col gap-2 rounded-xl border border-border/50 bg-background/50 p-3.5 transition-colors hover:border-border hover:bg-accent/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="flex min-w-0 items-start gap-3">
                                        <div
                                            className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white shadow-xs"
                                            style={{ backgroundColor: session.color_code || '#3b82f6' }}
                                        >
                                            {session.module_code.slice(0, 3)}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <h4 className="truncate text-sm font-semibold text-foreground">
                                                    {session.module_name}
                                                </h4>
                                                {isFirst && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="text-[10px] uppercase font-bold tracking-wider"
                                                    >
                                                        {t('dashboard.badge_next')}
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                                <span className="flex items-center gap-1 font-medium text-foreground/80">
                                                    <Clock className="size-3 text-muted-foreground" />
                                                    {dateLabel} · {timeLabel}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="size-3 text-muted-foreground" />
                                                    {session.room_name}
                                                    {session.building_name ? ` (${session.building_name})` : ''}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 text-xs text-muted-foreground sm:shrink-0">
                                        {session.groups.length > 0 && (
                                            <span className="flex items-center gap-1 rounded-md bg-muted/60 px-2 py-0.5">
                                                <Users className="size-3" />
                                                {session.groups.join(', ')}
                                            </span>
                                        )}
                                        <span className="flex items-center gap-1">
                                            <User className="size-3" />
                                            {session.teacher_name}
                                        </span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
});
