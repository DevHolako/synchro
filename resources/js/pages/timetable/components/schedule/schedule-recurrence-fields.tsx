import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useTranslation } from '@/i18n/LanguageContext';
import { MAX_BATCH_SLOTS, WEEKDAYS } from './schedule-recurrence';
import type { RecurrenceRule } from './schedule-recurrence';

const SELECT_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface ScheduleRecurrenceFieldsProps {
    rule: RecurrenceRule;
    onChange: (patch: Partial<RecurrenceRule>) => void;
}

export function ScheduleRecurrenceFields({
    rule,
    onChange,
}: ScheduleRecurrenceFieldsProps) {
    const { t } = useTranslation();

    return (
        <fieldset className="grid gap-3">
            <legend className="mb-1 text-sm font-semibold">
                {t('schedule.recurrence_title')}
            </legend>

            <div className="grid gap-2">
                <span className="text-sm font-medium">
                    {t('schedule.field_weekdays')}
                </span>
                <ToggleGroup
                    type="multiple"
                    variant="outline"
                    value={rule.weekdays.map(String)}
                    onValueChange={(values) =>
                        onChange({ weekdays: values.map(Number) })
                    }
                    className="flex-wrap"
                >
                    {WEEKDAYS.map((day) => (
                        <ToggleGroupItem
                            key={day}
                            value={String(day)}
                            className="px-2.5"
                        >
                            {t(`schedule.weekday_${day}`)}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="schedule_start_date">
                        {t('schedule.field_start_date')}
                    </Label>
                    <Input
                        id="schedule_start_date"
                        type="date"
                        value={rule.startDate}
                        onChange={(e) =>
                            onChange({ startDate: e.target.value })
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="schedule_end_mode">
                        {t('schedule.end_mode_label')}
                    </Label>
                    <select
                        id="schedule_end_mode"
                        value={rule.endMode}
                        onChange={(e) =>
                            onChange({
                                endMode: e.target
                                    .value as RecurrenceRule['endMode'],
                            })
                        }
                        className={SELECT_CLASS}
                    >
                        <option value="count">
                            {t('schedule.end_mode_count')}
                        </option>
                        <option value="until">
                            {t('schedule.end_mode_until')}
                        </option>
                    </select>
                </div>
                {rule.endMode === 'count' ? (
                    <div className="grid gap-2">
                        <Label htmlFor="schedule_count">
                            {t('schedule.field_count')}
                        </Label>
                        <Input
                            id="schedule_count"
                            type="number"
                            min={1}
                            max={MAX_BATCH_SLOTS}
                            value={rule.count}
                            onChange={(e) =>
                                onChange({ count: Number(e.target.value) })
                            }
                        />
                    </div>
                ) : (
                    <div className="grid gap-2">
                        <Label htmlFor="schedule_until">
                            {t('schedule.field_until')}
                        </Label>
                        <Input
                            id="schedule_until"
                            type="date"
                            min={rule.startDate}
                            value={rule.until}
                            onChange={(e) =>
                                onChange({ until: e.target.value })
                            }
                        />
                    </div>
                )}
            </div>
        </fieldset>
    );
}
