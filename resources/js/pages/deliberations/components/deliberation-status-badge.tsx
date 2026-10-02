import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/LanguageContext';
import type { DeliberationStatus } from './types';

const STATUS_CLASS: Record<DeliberationStatus, string> = {
    not_started: 'text-neutral-500',
    draft: 'border-amber-300 text-amber-700 dark:border-amber-900 dark:text-amber-300',
    submitted:
        'border-sky-300 text-sky-700 dark:border-sky-900 dark:text-sky-300',
    locked: 'border-emerald-300 text-emerald-700 dark:border-emerald-900 dark:text-emerald-300',
};

export function DeliberationStatusBadge({
    status,
}: {
    status: DeliberationStatus;
}) {
    const { t } = useTranslation();

    return (
        <Badge variant="outline" className={STATUS_CLASS[status]}>
            {t(`deliberations.status_${status}`)}
        </Badge>
    );
}
