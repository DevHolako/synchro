import { useTranslation } from '@/i18n/LanguageContext';
import { ScheduleDateList } from './schedule-date-list';
import { formatHours } from '../wall-clock-format';
import type { RecurrenceRule, TimeRange } from './schedule-recurrence';
import { ScheduleRecurrenceFields } from './schedule-recurrence-fields';
import { ScheduleTimeRanges } from './schedule-time-ranges';

interface ScheduleStepDatesProps {
    rule: RecurrenceRule;
    dates: string[];
    ranges: TimeRange[];
    rangesValid: boolean;
    slotCount: number;
    maxSlots: number;
    totalMinutes: number;
    onRuleChange: (patch: Partial<RecurrenceRule>) => void;
    onRemoveDate: (date: string) => void;
    onAddDate: (date: string) => void;
    onRangeChange: (id: number, patch: Partial<TimeRange>) => void;
    onAddRange: () => void;
    onRemoveRange: (id: number) => void;
}

export function ScheduleStepDates({
    rule,
    dates,
    ranges,
    rangesValid,
    slotCount,
    maxSlots,
    totalMinutes,
    onRuleChange,
    onRemoveDate,
    onAddDate,
    onRangeChange,
    onAddRange,
    onRemoveRange,
}: ScheduleStepDatesProps) {
    const { t, locale } = useTranslation();

    return (
        <div className="grid gap-5">
            <ScheduleRecurrenceFields
                rule={rule}
                maxDates={maxSlots}
                onChange={onRuleChange}
            />
            <ScheduleDateList
                dates={dates}
                onRemove={onRemoveDate}
                onAdd={onAddDate}
            />
            <ScheduleTimeRanges
                ranges={ranges}
                valid={rangesValid}
                onChange={onRangeChange}
                onAdd={onAddRange}
                onRemove={onRemoveRange}
            />
            <p className="text-sm font-medium">
                {t('schedule.slots_summary', {
                    slots: slotCount,
                    hours: formatHours(totalMinutes, locale),
                })}
            </p>
            {slotCount > maxSlots ? (
                <p className="text-sm text-red-600 dark:text-red-400">
                    {t('schedule.too_many_slots', {
                        max: maxSlots,
                        count: slotCount,
                    })}
                </p>
            ) : null}
        </div>
    );
}
