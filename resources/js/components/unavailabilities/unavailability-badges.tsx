import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/LanguageContext';
import type { UnavailabilityStatus, UnavailabilityType } from './types';

const STATUS_BADGE_CLASS: Record<UnavailabilityStatus, string> = {
    pending:
        'border-amber-500/30 bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400',
    approved:
        'border-emerald-500/30 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400',
    rejected:
        'border-rose-500/30 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400',
};

const TYPE_BADGE_CLASS: Record<UnavailabilityType, string> = {
    recurring_weekly:
        'border-indigo-500/30 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400',
    ad_hoc_date:
        'border-sky-500/30 bg-sky-50 text-sky-700 dark:bg-sky-950/20 dark:text-sky-400',
};

export function UnavailabilityStatusBadge({
    status,
}: {
    status: UnavailabilityStatus;
}) {
    const { t } = useTranslation();

    return (
        <Badge variant="outline" className={STATUS_BADGE_CLASS[status]}>
            {t(`unavailabilities.status_${status}`)}
        </Badge>
    );
}

export function UnavailabilityTypeBadge({
    type,
}: {
    type: UnavailabilityType;
}) {
    const { t } = useTranslation();

    return (
        <Badge variant="outline" className={TYPE_BADGE_CLASS[type]}>
            {t(`unavailabilities.type_${type}`)}
        </Badge>
    );
}
