import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { describeConflict } from '@/pages/timetable/components/schedule/conflict-description';
import type { SlotConflict } from '@/pages/timetable/components/schedule/types';

interface AllocationStaffConflictsProps {
    conflicts: SlotConflict[];
    justification: string;
    onJustificationChange: (justification: string) => void;
}

/** Declared unavailabilities of the chosen invigilators, and the justification to override them. */
export function AllocationStaffConflicts({
    conflicts,
    justification,
    onJustificationChange,
}: AllocationStaffConflictsProps) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-2 rounded-md border border-amber-300 bg-amber-50 p-2 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            <ul className="list-disc pl-4">
                {conflicts.map((conflict) => (
                    <li
                        key={`${conflict.resource_id}-${conflict.details.unavailability_id ?? ''}`}
                    >
                        {describeConflict(conflict, t)}
                    </li>
                ))}
            </ul>
            <textarea
                aria-label={t('exams.override_justification')}
                placeholder={t('exams.override_justification')}
                rows={2}
                maxLength={1000}
                value={justification}
                onChange={(e) => onJustificationChange(e.target.value)}
                className={FIELD_CLASS}
            />
        </div>
    );
}
