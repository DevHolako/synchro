import { TriangleAlert } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { describeConflict } from '@/pages/timetable/components/schedule/conflict-description';
import type { SlotConflict } from '@/pages/timetable/components/schedule/types';

/** Clashes the exam would cause once scheduled: a warning for a draft, a refusal on scheduling. */
export function ExamConflictWarnings({
    conflicts,
}: {
    conflicts: SlotConflict[];
}) {
    const { t } = useTranslation();

    if (conflicts.length === 0) {
        return null;
    }

    return (
        <div className="flex gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            <TriangleAlert className="mt-0.5 size-4 shrink-0" />
            <div>
                <p className="font-medium">{t('exams.conflicts_warning')}</p>
                <ul className="mt-1 list-disc pl-4">
                    {conflicts.map((conflict) => (
                        <li
                            key={`${conflict.booking_type}-${conflict.booking_id}-${conflict.resource_id}`}
                        >
                            {describeConflict(conflict, t)}
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
