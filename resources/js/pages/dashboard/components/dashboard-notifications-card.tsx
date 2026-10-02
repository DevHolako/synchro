import { Link } from '@inertiajs/react';
import { ArrowRight, Bell, Calendar, Mail } from 'lucide-react';
import React, { memo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import { index as notificationsIndex } from '@/routes/notifications';
import type { DashboardNotificationItem } from './types';

interface DashboardNotificationsCardProps {
    notifications: DashboardNotificationItem[];
}

function formatRelativeTime(dateStr: string, t: (key: string, params?: Record<string, string | number>) => string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffSec < 60) {
        return t('notifications.just_now');
    }
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) {
        return t('notifications.minutes_ago', { count: diffMin });
    }
    const diffHours = Math.floor(diffMin / 60);
    if (diffHours < 24) {
        return t('notifications.hours_ago', { count: diffHours });
    }
    const diffDays = Math.floor(diffHours / 24);
    return t('notifications.days_ago', { count: diffDays });
}

export const DashboardNotificationsCard = memo(function DashboardNotificationsCard({
    notifications,
}: DashboardNotificationsCardProps) {
    const { t } = useTranslation();

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-xs">
            <CardHeader className="flex flex-row items-center justify-between pb-3">
                <div className="space-y-1">
                    <CardTitle className="text-base font-semibold">
                        {t('dashboard.recent_notifications_title')}
                    </CardTitle>
                    <CardDescription className="text-xs">
                        {t('dashboard.recent_notifications_subtitle')}
                    </CardDescription>
                </div>
                <Button variant="ghost" size="sm" asChild className="gap-1 text-xs">
                    <Link href={notificationsIndex()}>
                        {t('dashboard.recent_notifications_view_all')}
                        <ArrowRight className="size-3.5" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                {notifications.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-6 text-center">
                        <Bell className="size-9 text-muted-foreground/40" />
                        <p className="mt-2 text-xs font-medium text-muted-foreground">
                            {t('dashboard.recent_notifications_empty')}
                        </p>
                    </div>
                ) : (
                    <div className="divide-y divide-border/50">
                        {notifications.map((item) => {
                            const title = item.data.title || t('notifications.title');
                            const message = item.data.message || '';
                            const timeLabel = formatRelativeTime(item.created_at, t);

                            return (
                                <Link
                                    key={item.id}
                                    href={notificationsIndex()}
                                    className="group flex items-start gap-3 py-3 transition-colors first:pt-0 last:pb-0 hover:text-foreground"
                                >
                                    <div className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        {item.data.convocation_url ? (
                                            <Calendar className="size-3.5" />
                                        ) : (
                                            <Mail className="size-3.5" />
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-2">
                                            <h5 className="truncate text-xs font-semibold text-foreground group-hover:text-primary">
                                                {title}
                                            </h5>
                                            <span className="shrink-0 text-[10px] text-muted-foreground">
                                                {timeLabel}
                                            </span>
                                        </div>
                                        {message && (
                                            <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                                                {message}
                                            </p>
                                        )}
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
});
