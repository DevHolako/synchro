import { useTranslation } from '@/i18n/LanguageContext';
import { DELIBERATION_STATUSES } from './types';
import type { DeliberationStats as Stats } from './types';

/** How many sheets stand at each status in the period. */
export function DeliberationStats({ stats }: { stats: Stats }) {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {DELIBERATION_STATUSES.map((status) => (
                <div
                    key={status}
                    className="rounded-lg border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div className="text-xs text-neutral-500">
                        {t(`deliberations.status_${status}`)}
                    </div>
                    <div className="text-xl font-bold">{stats[status]}</div>
                </div>
            ))}
        </div>
    );
}
