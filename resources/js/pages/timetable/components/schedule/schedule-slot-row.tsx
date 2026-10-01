import { CircleCheck, CircleX, TriangleAlert, X } from 'lucide-react';
import { memo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { describeConflict } from './conflict-description';
import { formatDay } from './schedule-format';
import type { BatchSlot, CheckedSlot } from './types';

interface ScheduleSlotRowProps {
    slot: CheckedSlot;
    /** The slots of this batch it overlaps. */
    overlapping: BatchSlot[];
    onRemove: (startsAt: string) => void;
}

const timeOf = (value: string) => value.slice(11, 16);

export const ScheduleSlotRow = memo(function ScheduleSlotRow({
    slot,
    overlapping,
    onRemove,
}: ScheduleSlotRowProps) {
    const { t, locale } = useTranslation();
    const blocked = slot.has_hard_conflicts || overlapping.length > 0;
    const problems = [
        ...overlapping.map((other) =>
            t('schedule.slot_overlap', {
                date: formatDay(other.starts_at.slice(0, 10), locale, 'medium'),
                start: timeOf(other.starts_at),
                end: timeOf(other.ends_at),
            }),
        ),
        ...slot.hard_conflicts.map((conflict) => describeConflict(conflict, t)),
    ];
    const warnings = slot.soft_conflicts.map((conflict) =>
        describeConflict(conflict, t),
    );

    return (
        <li className="flex items-start gap-3 py-2">
            {blocked ? (
                <CircleX className="mt-0.5 size-4 shrink-0 text-red-600" />
            ) : slot.has_soft_conflicts ? (
                <TriangleAlert className="mt-0.5 size-4 shrink-0 text-amber-600" />
            ) : (
                <CircleCheck className="mt-0.5 size-4 shrink-0 text-emerald-600" />
            )}
            <div className="min-w-0 flex-1 text-sm">
                <p className="font-medium">
                    {formatDay(slot.starts_at.slice(0, 10), locale)} ·{' '}
                    {timeOf(slot.starts_at)}–{timeOf(slot.ends_at)}
                </p>
                {problems.length === 0 && warnings.length === 0 ? (
                    <p className="text-neutral-500">
                        {t('schedule.slot_free')}
                    </p>
                ) : null}
                {problems.map((problem) => (
                    <p key={problem} className="text-red-700 dark:text-red-400">
                        {problem}
                    </p>
                ))}
                {warnings.map((warning) => (
                    <p
                        key={warning}
                        className="text-amber-700 dark:text-amber-400"
                    >
                        {warning}
                    </p>
                ))}
            </div>
            <button
                type="button"
                onClick={() => onRemove(slot.starts_at)}
                aria-label={t('schedule.remove_slot')}
                className="rounded p-1 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800"
            >
                <X className="size-4" />
            </button>
        </li>
    );
});
