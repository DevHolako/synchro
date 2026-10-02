import { useTranslation } from '@/i18n/LanguageContext';
import { EXAM_STATES } from './types';
import type { ExamStats } from './types';

export function ExamStatsCards({ stats }: { stats: ExamStats }) {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
            {EXAM_STATES.map((state) => (
                <div
                    key={state}
                    className="rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div className="text-xs font-medium text-neutral-500">
                        {t(`exams.state_${state}`)}
                    </div>
                    <div className="mt-1 text-2xl font-bold">
                        {stats[state]}
                    </div>
                </div>
            ))}
        </div>
    );
}
