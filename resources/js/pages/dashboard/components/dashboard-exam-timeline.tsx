import { Link } from '@inertiajs/react';
import { ArrowRight, Award, Calendar, Clock, MapPin } from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import { index as examsIndex } from '@/routes/exams';
import type { DashboardExamItem } from './types';

interface DashboardExamTimelineProps {
    exams: DashboardExamItem[];
}

function formatExamTime(startsAt: string, endsAt: string): string {
    const start = new Date(startsAt);
    const end = new Date(endsAt);
    const startStr = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const endStr = end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return `${startStr} – ${endStr}`;
}

function formatExamDate(dateStr: string, locale: string): string {
    const date = new Date(dateStr);
    return date.toLocaleDateString(locale === 'fr' ? 'fr-FR' : 'en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function getStateBadgeVariant(state: string): 'default' | 'secondary' | 'outline' {
    switch (state) {
        case 'published':
            return 'default';
        case 'scheduled':
            return 'secondary';
        default:
            return 'outline';
    }
}

export const DashboardExamTimeline = memo(function DashboardExamTimeline({
    exams,
}: DashboardExamTimelineProps) {
    const { t, locale } = useTranslation();

    const getStateLabel = (state: string): string => {
        switch (state) {
            case 'draft':
                return t('dashboard.state_draft');
            case 'scheduled':
                return t('dashboard.state_scheduled');
            case 'published':
                return t('dashboard.state_published');
            case 'completed':
                return t('dashboard.state_completed');
            case 'archived':
                return t('dashboard.state_archived');
            default:
                return state;
        }
    };

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-xs">
            <CardHeader className="flex flex-row items-center justify-between pb-3">
                <div className="space-y-1">
                    <CardTitle className="text-base font-semibold">
                        {t('dashboard.upcoming_exams_title')}
                    </CardTitle>
                    <CardDescription className="text-xs">
                        {t('dashboard.upcoming_exams_subtitle')}
                    </CardDescription>
                </div>
                <Button variant="ghost" size="sm" asChild className="gap-1 text-xs">
                    <Link href={examsIndex()}>
                        {t('dashboard.upcoming_exams_view_all')}
                        <ArrowRight className="size-3.5" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                {exams.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <Award className="size-10 text-muted-foreground/40" />
                        <p className="mt-3 text-sm font-medium text-muted-foreground">
                            {t('dashboard.upcoming_exams_empty')}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {exams.map((exam) => {
                            const dateLabel = formatExamDate(exam.starts_at, locale);
                            const timeLabel = formatExamTime(exam.starts_at, exam.ends_at);

                            return (
                                <div
                                    key={exam.id}
                                    className="group relative flex flex-col gap-2 rounded-xl border border-border/50 bg-background/50 p-3.5 transition-colors hover:border-border hover:bg-accent/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="flex min-w-0 items-start gap-3">
                                        <div
                                            className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white shadow-xs"
                                            style={{ backgroundColor: exam.color_code || '#8b5cf6' }}
                                        >
                                            {exam.module_code.slice(0, 3)}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <h4 className="truncate text-sm font-semibold text-foreground">
                                                    {exam.module_name}
                                                </h4>
                                                <Badge
                                                    variant={getStateBadgeVariant(exam.state)}
                                                    className="text-[10px] capitalize"
                                                >
                                                    {getStateLabel(exam.state)}
                                                </Badge>
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                                <span className="flex items-center gap-1 font-medium text-foreground/80">
                                                    <Calendar className="size-3 text-muted-foreground" />
                                                    {dateLabel} · {timeLabel}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Clock className="size-3 text-muted-foreground" />
                                                    {exam.period_name}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {exam.rooms.length > 0 && (
                                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground sm:shrink-0">
                                            <MapPin className="size-3 text-muted-foreground" />
                                            <span>{exam.rooms.join(', ')}</span>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
});
