import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/LanguageContext';
import { GRID_END, GRID_START, GRID_STEP_SECONDS } from '@/lib/scheduling-grid';
import type { TimeRange } from './schedule-recurrence';

interface ScheduleTimeRangesProps {
    ranges: TimeRange[];
    valid: boolean;
    onChange: (id: number, patch: Partial<TimeRange>) => void;
    onAdd: () => void;
    onRemove: (id: number) => void;
}

export function ScheduleTimeRanges({
    ranges,
    valid,
    onChange,
    onAdd,
    onRemove,
}: ScheduleTimeRangesProps) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-2">
            <span className="text-sm font-semibold">
                {t('schedule.ranges_title')}
            </span>
            <p className="text-xs text-neutral-500">
                {t('schedule.ranges_hint')}
            </p>
            {ranges.map((range) => (
                <div key={range.id} className="flex items-center gap-2">
                    <Input
                        type="time"
                        aria-label={t('schedule.range_start')}
                        step={GRID_STEP_SECONDS}
                        min={GRID_START}
                        max={GRID_END}
                        value={range.start}
                        onChange={(e) =>
                            onChange(range.id, { start: e.target.value })
                        }
                        className="w-32"
                    />
                    <span className="text-neutral-400">–</span>
                    <Input
                        type="time"
                        aria-label={t('schedule.range_end')}
                        step={GRID_STEP_SECONDS}
                        min={GRID_START}
                        max={GRID_END}
                        value={range.end}
                        onChange={(e) =>
                            onChange(range.id, { end: e.target.value })
                        }
                        className="w-32"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => onRemove(range.id)}
                        disabled={ranges.length === 1}
                        aria-label={t('schedule.remove_range')}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            ))}
            {valid ? null : (
                <p className="text-xs text-red-600 dark:text-red-400">
                    {t('schedule.ranges_invalid')}
                </p>
            )}
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onAdd}
                >
                    <Plus className="size-4" />
                    {t('schedule.add_range')}
                </Button>
            </div>
        </div>
    );
}
