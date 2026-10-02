import { Link } from '@inertiajs/react';
import { CalendarPlus } from 'lucide-react';
import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { ExamStateBadge } from '@/pages/exams/components/exam-state-badge';
import { index as examsIndex } from '@/routes/exams';
import type { RetakeModule } from './types';

interface RetakeModuleCardProps {
    module: RetakeModule;
    periodId: number;
    onCreate: (module: RetakeModule) => void;
}

/** One module's failing students, by group, and the way to its retake exam. */
export const RetakeModuleCard = memo(function RetakeModuleCard({
    module,
    periodId,
    onCreate,
}: RetakeModuleCardProps) {
    const { t } = useTranslation();

    return (
        <section className="rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <header className="flex flex-col gap-2 border-b border-neutral-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
                <div>
                    <h2 className="font-semibold">{module.module}</h2>
                    <p className="text-sm text-neutral-500">
                        {t('retakes.students_count', {
                            count: module.students.length,
                        })}
                    </p>
                </div>
                {module.exam ? (
                    <div className="flex items-center gap-2 text-sm">
                        <span className="text-neutral-500">
                            {t('retakes.exam_created')}
                        </span>
                        <ExamStateBadge
                            state={module.exam.state}
                            overdue={false}
                        />
                        <Link
                            href={examsIndex.url({
                                query: { period: periodId },
                            })}
                            className="font-medium text-sky-700 hover:underline dark:text-sky-300"
                        >
                            {t('retakes.open_exams')}
                        </Link>
                    </div>
                ) : (
                    <Button
                        size="sm"
                        disabled={module.group_ids.length === 0}
                        onClick={() => onCreate(module)}
                    >
                        <CalendarPlus className="mr-1 size-4" />
                        {t('retakes.create')}
                    </Button>
                )}
            </header>
            {module.ungrouped > 0 ? (
                <p className="border-b border-neutral-200 bg-amber-50 px-4 py-2 text-sm text-amber-900 dark:border-neutral-800 dark:bg-amber-950/40 dark:text-amber-200">
                    {t('retakes.ungrouped', { count: module.ungrouped })}
                </p>
            ) : null}
            <table className="w-full text-left text-sm">
                <thead className="text-xs text-neutral-500">
                    <tr>
                        <th className="px-4 py-2">
                            {t('retakes.col_student')}
                        </th>
                        <th className="px-4 py-2">{t('retakes.col_group')}</th>
                        <th className="px-4 py-2 text-right">
                            {t('retakes.col_final')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                    {module.students.map((student) => (
                        <tr key={student.student_id}>
                            <td className="px-4 py-2">
                                <div className="font-medium">
                                    {student.name}
                                </div>
                                <div className="text-xs text-neutral-500">
                                    {student.student_number ?? '—'}
                                </div>
                            </td>
                            <td className="px-4 py-2">
                                {student.group ?? '—'}
                            </td>
                            <td className="px-4 py-2 text-right font-mono text-rose-600">
                                {student.final_grade ?? '—'}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </section>
    );
});
