import { useTranslation } from '@/i18n/LanguageContext';
import { SyllabusProgressRow } from './syllabus-progress-row';
import type { SyllabusProgress } from './types';

interface SyllabusProgressPanelProps {
    modules: SyllabusProgress[];
    activeModuleId: number | null;
    onToggleModule: (moduleId: number) => void;
}

export function SyllabusProgressPanel({
    modules,
    activeModuleId,
    onToggleModule,
}: SyllabusProgressPanelProps) {
    const { t } = useTranslation();

    return (
        <aside className="flex flex-col gap-3 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div>
                <h2 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('timetable.syllabus_title')}
                </h2>
                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                    {t('timetable.syllabus_desc')}
                </p>
            </div>

            {modules.length === 0 ? (
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    {t('timetable.syllabus_empty')}
                </p>
            ) : (
                <>
                    <ul className="-mx-1 flex flex-col gap-1">
                        {modules.map((module) => (
                            <SyllabusProgressRow
                                key={module.module_id}
                                moduleId={module.module_id}
                                code={module.code}
                                name={module.name}
                                colorCode={module.color_code}
                                totalHours={module.total_hours}
                                plannedMinutes={module.planned_minutes}
                                active={module.module_id === activeModuleId}
                                onToggle={onToggleModule}
                            />
                        ))}
                    </ul>
                    <p className="text-xs text-neutral-400 dark:text-neutral-500">
                        {t('timetable.syllabus_hint')}
                    </p>
                </>
            )}
        </aside>
    );
}
