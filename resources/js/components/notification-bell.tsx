import { router, usePage } from '@inertiajs/react';
import { Bell, CalendarDays, CheckCheck, ClipboardList } from 'lucide-react';
import React, { useCallback, useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';

interface NotificationItem {
    id: string;
    data: {
        title?: string;
        message?: string;
        type?: string;
        link?: string;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string;
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

export function NotificationBell() {
    const { t } = useTranslation();
    const { unreadNotificationsCount: initialUnreadCount } = usePage().props;
    const [unreadCount, setUnreadCount] = useState<number>(
        initialUnreadCount ?? 0,
    );
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setUnreadCount(initialUnreadCount);
    }, [initialUnreadCount]);

    const loadNotifications = useCallback(async () => {
        setLoading(true);
        try {
            const res = await fetch('/notifications', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (res.ok) {
                const data = await res.json();
                setNotifications(data.notifications ?? []);
                if (typeof data.unread_count === 'number') {
                    setUnreadCount(data.unread_count);
                }
            }
        } catch {
            // Silently ignore transient network fetch failures
        } finally {
            setLoading(false);
        }
    }, []);

    const handleOpenChange = (open: boolean) => {
        setIsOpen(open);
        if (open) {
            void loadNotifications();
        }
    };

    const markAsRead = async (notification: NotificationItem) => {
        if (!notification.read_at) {
            setNotifications((prev) =>
                prev.map((n) =>
                    n.id === notification.id
                        ? { ...n, read_at: new Date().toISOString() }
                        : n,
                ),
            );
            setUnreadCount((prev) => Math.max(0, prev - 1));

            try {
                await fetch(`/notifications/${notification.id}/read`, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN':
                            (
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                ) as HTMLMetaElement
                            )?.content ?? '',
                    },
                });
            } catch {
                // Ignore failure
            }
        }

        if (notification.data.link) {
            setIsOpen(false);
            router.visit(notification.data.link);
        }
    };

    const markAllAsRead = async () => {
        setNotifications((prev) =>
            prev.map((n) => ({ ...n, read_at: new Date().toISOString() })),
        );
        setUnreadCount(0);

        try {
            await fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content ?? '',
                },
            });
        } catch {
            // Ignore failure
        }
    };

    return (
        <DropdownMenu open={isOpen} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative size-9 cursor-pointer text-muted-foreground hover:text-foreground"
                    aria-label={t('notifications.title')}
                >
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex size-4 animate-in items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white shadow-sm zoom-in-50">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                className="w-80 p-0 sm:w-96"
                sideOffset={8}
            >
                <div className="flex items-center justify-between border-b px-4 py-3">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-semibold">
                            {t('notifications.title')}
                        </span>
                        {unreadCount > 0 && (
                            <Badge
                                variant="secondary"
                                className="h-5 px-1.5 text-[11px]"
                            >
                                {unreadCount}
                            </Badge>
                        )}
                    </div>
                    {unreadCount > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={markAllAsRead}
                            className="h-7 text-xs text-muted-foreground hover:text-foreground"
                        >
                            <CheckCheck className="mr-1 size-3.5" />
                            {t('notifications.mark_all_read')}
                        </Button>
                    )}
                </div>

                <div className="max-h-80 divide-y overflow-y-auto">
                    {loading && notifications.length === 0 ? (
                        <div className="flex items-center justify-center py-8 text-sm text-muted-foreground">
                            <span className="size-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                        </div>
                    ) : notifications.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-8 text-center text-sm text-muted-foreground">
                            <Bell className="mb-2 size-8 opacity-20" />
                            <p>{t('notifications.empty')}</p>
                        </div>
                    ) : (
                        notifications.map((notification) => {
                            const isUnread = !notification.read_at;
                            return (
                                <DropdownMenuItem
                                    key={notification.id}
                                    onClick={() => markAsRead(notification)}
                                    className={cn(
                                        'flex cursor-pointer items-start gap-3 p-3 text-left transition-colors focus:bg-accent',
                                        isUnread &&
                                            'bg-muted/40 font-medium dark:bg-muted/20',
                                    )}
                                >
                                    <div className="mt-0.5">
                                        <NotificationIcon
                                            type={notification.data.type}
                                        />
                                    </div>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <p className="line-clamp-1 text-xs font-semibold text-foreground">
                                            {notification.data.title ??
                                                t('notifications.title')}
                                        </p>
                                        {notification.data.message && (
                                            <p className="line-clamp-2 text-xs text-muted-foreground">
                                                {notification.data.message}
                                            </p>
                                        )}
                                        <span className="text-[10px] text-muted-foreground/80">
                                            {formatRelativeTime(
                                                notification.created_at,
                                                t,
                                            )}
                                        </span>
                                    </div>
                                    {isUnread && (
                                        <span className="mt-1.5 size-2 shrink-0 rounded-full bg-blue-600" />
                                    )}
                                </DropdownMenuItem>
                            );
                        })
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
