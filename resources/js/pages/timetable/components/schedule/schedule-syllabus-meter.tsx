import { useTranslation } from '@/i18n/LanguageContext';
import { formatHours } from '../wall-clock-format';
import type { BatchCheckResponse } from './types';

interface ScheduleSyllabusMeterProps {
    syllabus: BatchCheckResponse['syllabus'];
}

const MINUTES_PER_HOUR = 60;

const percentOf = (minutes: number, totalMinutes: number) =>
    totalMinutes > 0 ? Math.min((minutes / totalMinutes) * 100, 100) : 100;

/** Per group: hours already planned, what this batch adds, against the syllabus. */
export function ScheduleSyllabusMeter({
    syllabus,
}: ScheduleSyllabusMeterProps) {
    const { t, locale } = useTranslation();
    const totalMinutes = syllabus.total_hours * MINUTES_PER_HOUR;

    return (
        <div className="grid gap-3 rounded-md border border-neutral-200 p-3 dark:border-neutral-800">
            <p className="text-sm font-semibold">{t('schedule.meter_title')}</p>
            {syllabus.groups.map((group) => {
                const after = group.planned_minutes + syllabus.batch_minutes;
                const plannedPercent = percentOf(
                    group.planned_minutes,
                    totalMinutes,
                );
                const batchPercent =
                    percentOf(after, totalMinutes) - plannedPercent;
                const overMinutes = after - totalMinutes;

                return (
                    <div key={group.id} className="grid gap-1 text-xs">
                        <div className="flex justify-between gap-2">
                            <span className="font-medium">{group.name}</span>
                            <span className="text-neutral-500">
                                {t('schedule.meter_line', {
                                    planned: formatHours(
                                        group.planned_minutes,
                                        locale,
                                    ),
                                    batch: formatHours(
                                        syllabus.batch_minutes,
                                        locale,
                                    ),
                                    total: syllabus.total_hours,
                                })}
                            </span>
                        </div>
                        <div className="flex h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <div
                                className="h-full bg-emerald-500"
                                style={{ width: `${plannedPercent}%` }}
                            />
                            <div
                                className={
                                    overMinutes > 0
                                        ? 'h-full bg-red-500'
                                        : 'h-full bg-sky-500'
                                }
                                style={{ width: `${batchPercent}%` }}
                            />
                        </div>
                        {overMinutes > 0 ? (
                            <span className="text-red-600 dark:text-red-400">
                                {t('schedule.meter_over', {
                                    hours: formatHours(overMinutes, locale),
                                })}
                            </span>
                        ) : null}
                    </div>
                );
            })}
        </div>
    );
}
