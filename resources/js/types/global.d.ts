import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            /** The zone session times are in (config app.schedule_timezone). */
            scheduleTimezone: string;
            auth: Auth;
            sidebarOpen: boolean;
            pendingUnavailabilityCount: number | null;
            unreadNotificationsCount: number;
            theme: {
                color: string;
                radius: string;
                mode: string;
            };
            [key: string]: unknown;
        };
    }
}
