import { ClipboardList } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { ExamRow } from './exam-row';
import type { Exam, ExamConfirmation } from './types';

interface ExamTableProps {
    exams: Exam[];
    canManage: boolean;
    onEdit: (exam: Exam) => void;
    onAllocate: (exam: Exam) => void;
    onConfirm: (confirmation: ExamConfirmation) => void;
}

export function ExamTable({
    exams,
    canManage,
    onEdit,
    onAllocate,
    onConfirm,
}: ExamTableProps) {
    const { t } = useTranslation();

    if (exams.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <ClipboardList className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('exams.empty_title')}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {t(
                        canManage
                            ? 'exams.empty_desc_manage'
                            : 'exams.empty_desc',
                    )}
                </p>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-6 py-3">{t('exams.col_when')}</th>
                        <th className="px-6 py-3">{t('exams.col_module')}</th>
                        <th className="px-6 py-3">{t('exams.col_groups')}</th>
                        <th className="px-6 py-3">{t('common.status')}</th>
                        {canManage ? (
                            <th className="px-6 py-3 text-right">
                                {t('common.actions')}
                            </th>
                        ) : null}
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {exams.map((exam) => (
                        <ExamRow
                            key={exam.id}
                            exam={exam}
                            canManage={canManage}
                            onEdit={onEdit}
                            onAllocate={onAllocate}
                            onConfirm={onConfirm}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
