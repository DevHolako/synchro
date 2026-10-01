import { memo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';

const MINUTES_PER_HOUR = 60;

interface SyllabusProgressRowProps {
    moduleId: number;
    code: string;
    name: string;
    colorCode: string;
    totalHours: number;
    plannedMinutes: number;
    active: boolean;
    onToggle: (moduleId: number) => void;
}

export const SyllabusProgressRow = memo(function SyllabusProgressRow({
    moduleId,
    code,
    name,
    colorCode,
    totalHours,
    plannedMinutes,
    active,
    onToggle,
}: SyllabusProgressRowProps) {
    const { t, locale } = useTranslation();
    const hours = new Intl.NumberFormat(locale, { maximumFractionDigits: 2 });
    const planned = plannedMinutes / MINUTES_PER_HOUR;
    const percent =
        totalHours > 0 ? Math.round((planned / totalHours) * 100) : 100;
    const overHours = planned - totalHours;
    const over = overHours > 0;

    return (
        <li>
            <button
                type="button"
                onClick={() => onToggle(moduleId)}
                aria-pressed={active}
                className={cn(
                    'flex w-full flex-col gap-1.5 rounded-md border px-3 py-2 text-left transition-colors',
                    active
                        ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-100 dark:bg-neutral-800'
                        : 'border-transparent hover:bg-neutral-50 dark:hover:bg-neutral-800/60',
                )}
            >
                <div className="flex items-center gap-2 text-sm">
                    <span
                        className="size-2.5 shrink-0 rounded-full"
                        style={{ backgroundColor: colorCode }}
                    />
                    <span className="shrink-0 font-medium text-neutral-900 dark:text-neutral-100">
                        {code}
                    </span>
                    <span className="truncate text-neutral-500 dark:text-neutral-400">
                        {name}
                    </span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                    <div
                        className={cn(
                            'h-full rounded-full',
                            over ? 'bg-red-500' : 'bg-emerald-500',
                        )}
                        style={{ width: `${Math.min(percent, 100)}%` }}
                    />
                </div>
                <div className="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
                    <span>
                        {t('timetable.syllabus_hours', {
                            planned: hours.format(planned),
                            total: hours.format(totalHours),
                        })}
                    </span>
                    {over ? (
                        <span className="font-medium text-red-600 dark:text-red-400">
                            {t('timetable.syllabus_over', {
                                hours: hours.format(overHours),
                            })}
                        </span>
                    ) : (
                        <span>{percent}%</span>
                    )}
                </div>
            </button>
        </li>
    );
});
