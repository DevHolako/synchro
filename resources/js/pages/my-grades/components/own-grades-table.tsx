import { useTranslation } from '@/i18n/LanguageContext';
import { OwnGradeRow } from './own-grade-row';
import type { OwnGrade } from './types';

export function OwnGradesTable({ grades }: { grades: OwnGrade[] }) {
    const { t } = useTranslation();

    if (grades.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                {t('my_grades.empty')}
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-4 py-3">
                            {t('my_grades.col_module')}
                        </th>
                        <th className="px-4 py-3">
                            {t('my_grades.col_period')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('my_grades.col_cc')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('my_grades.col_exam')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('my_grades.col_final')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('my_grades.col_result')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {grades.map((grade) => (
                        <OwnGradeRow key={grade.exam_id} grade={grade} />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
