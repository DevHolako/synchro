import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import React, { useCallback, useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/i18n/LanguageContext';
import { NotificationItem } from './notifications/notification-item';
import type { NotificationItemData } from './notifications/types';

export function NotificationBell() {
    const { t } = useTranslation();
    const { unreadNotificationsCount: initialUnreadCount } = usePage().props;
    const [unreadCount, setUnreadCount] = useState<number>(
        initialUnreadCount ?? 0,
    );
    const [notifications, setNotifications] = useState<NotificationItemData[]>(
        [],
    );
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

    const markAsRead = async (notification: NotificationItemData) => {
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
                        notifications.map((notification) => (
                            <NotificationItem
                                key={notification.id}
                                notification={notification}
                                onSelect={markAsRead}
                            />
                        ))
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
