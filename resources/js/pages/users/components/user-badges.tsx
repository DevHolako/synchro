import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/LanguageContext';
import type { AccountStatus, UserRole } from './types';

const ROLE_BADGE_CLASS: Record<UserRole, string> = {
    administrator:
        'border-rose-500/30 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400',
    coordinator:
        'border-sky-500/30 bg-sky-50 text-sky-700 dark:bg-sky-950/20 dark:text-sky-400',
    teacher:
        'border-indigo-500/30 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400',
    student:
        'border-emerald-500/30 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400',
};

const STATUS_BADGE_CLASS: Record<AccountStatus, string> = {
    active: 'border-emerald-500/30 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400',
    invited:
        'border-amber-500/30 bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400',
};

export function RoleBadge({ role }: { role: UserRole }) {
    const { t } = useTranslation();

    return (
        <Badge variant="outline" className={ROLE_BADGE_CLASS[role]}>
            {t(`users.role_${role}`)}
        </Badge>
    );
}

export function StatusBadge({ status }: { status: AccountStatus }) {
    const { t } = useTranslation();

    return (
        <Badge variant="outline" className={STATUS_BADGE_CLASS[status]}>
            {t(`users.status_${status}`)}
        </Badge>
    );
}
