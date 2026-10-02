import { useTranslation } from '@/i18n/LanguageContext';
import { GradeRow } from './grade-row';
import type { GradeDraft, GradeRow as GradeRowData } from './types';

interface GradeTableProps {
    drafts: ReadonlyArray<readonly [GradeRowData, GradeDraft]>;
    continuousAssessmentWeight: number;
    editable: boolean;
    onChange: (studentId: number, patch: Partial<GradeDraft>) => void;
}

export function GradeTable({
    drafts,
    continuousAssessmentWeight,
    editable,
    onChange,
}: GradeTableProps) {
    const { t } = useTranslation();

    if (drafts.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                {t('grades.empty')}
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-4 py-3">{t('grades.col_student')}</th>
                        {continuousAssessmentWeight > 0 ? (
                            <th className="px-4 py-3">{t('grades.col_cc')}</th>
                        ) : null}
                        <th className="px-4 py-3">{t('grades.col_exam')}</th>
                        <th className="px-4 py-3 text-center">
                            {t('grades.col_absent')}
                        </th>
                        <th className="px-4 py-3">{t('grades.col_remarks')}</th>
                        <th className="px-4 py-3 text-right">
                            {t('grades.col_final')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {drafts.map(([row, draft]) => (
                        <GradeRow
                            key={row.student_id}
                            row={row}
                            draft={draft}
                            continuousAssessmentWeight={
                                continuousAssessmentWeight
                            }
                            editable={editable}
                            onChange={onChange}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
