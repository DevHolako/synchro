import { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ExamState } from './types';

const STATE_CLASSES: Record<ExamState, string> = {
    draft: 'border-neutral-300 bg-neutral-100 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    scheduled:
        'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
    published:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    completed:
        'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-300',
    archived:
        'border-neutral-200 bg-white text-neutral-500 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400',
};

export const ExamStateBadge = memo(function ExamStateBadge({
    state,
    overdue,
}: {
    state: ExamState;
    overdue: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap gap-1">
            <Badge variant="outline" className={STATE_CLASSES[state]}>
                {t(`exams.state_${state}`)}
            </Badge>
            {overdue ? (
                <Badge
                    variant="outline"
                    className="border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300"
                >
                    {t('exams.overdue')}
                </Badge>
            ) : null}
        </div>
    );
});
