import React, { memo } from 'react';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import { Bell, CalendarDays, ClipboardList } from 'lucide-react';
import type { NotificationItemData } from './types';

interface NotificationItemProps {
    notification: NotificationItemData;
    onSelect: (notification: NotificationItemData) => void;
}

function formatRelativeTime(
    dateString: string,
    t: (key: string, params?: Record<string, string | number>) => string,
): string {
    const diffMs = Date.now() - new Date(dateString).getTime();
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMinutes < 1) {
        return t('notifications.just_now');
    }
    if (diffMinutes < 60) {
        return t('notifications.minutes_ago', { count: diffMinutes });
    }
    if (diffHours < 24) {
        return t('notifications.hours_ago', { count: diffHours });
    }
    return t('notifications.days_ago', { count: diffDays });
}

function NotificationIcon({ type }: { type?: string }) {
    if (type === 'timetable_published') {
        return <CalendarDays className="size-4 shrink-0 text-sky-500" />;
    }
    if (type === 'exam_convocation_published') {
        return <ClipboardList className="size-4 shrink-0 text-amber-500" />;
    }
    return <Bell className="size-4 shrink-0 text-muted-foreground" />;
}

export const NotificationItem = memo(function NotificationItem({
    notification,
    onSelect,
}: NotificationItemProps) {
    const { t } = useTranslation();
    const isUnread = !notification.read_at;

    return (
        <DropdownMenuItem
            onClick={() => onSelect(notification)}
            className={cn(
                'flex cursor-pointer items-start gap-3 p-3 text-left transition-colors focus:bg-accent',
                isUnread && 'bg-muted/40 font-medium dark:bg-muted/20',
            )}
        >
            <div className="mt-0.5">
                <NotificationIcon type={notification.data.type} />
            </div>
            <div className="min-w-0 flex-1 space-y-1">
                <p className="line-clamp-1 text-xs font-semibold text-foreground">
                    {notification.data.title ?? t('notifications.title')}
                </p>
                {notification.data.message && (
                    <p className="line-clamp-2 text-xs text-muted-foreground">
                        {notification.data.message}
                    </p>
                )}
                <span className="text-[10px] text-muted-foreground/80">
                    {formatRelativeTime(notification.created_at, t)}
                </span>
            </div>
            {isUnread && (
                <span className="mt-1.5 size-2 shrink-0 rounded-full bg-blue-600" />
            )}
        </DropdownMenuItem>
    );
});
