import { useTranslation } from '@/i18n/LanguageContext';
import { DeliberationRow } from './deliberation-row';
import type { DeliberationSheet } from './types';

export function DeliberationTable({ sheets }: { sheets: DeliberationSheet[] }) {
    const { t } = useTranslation();

    if (sheets.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                {t('deliberations.empty')}
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-4 py-3">
                            {t('deliberations.col_exam')}
                        </th>
                        <th className="px-4 py-3">
                            {t('deliberations.col_teacher')}
                        </th>
                        <th className="px-4 py-3">
                            {t('deliberations.col_status')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('deliberations.col_lines')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('deliberations.col_average')}
                        </th>
                        <th className="px-4 py-3 text-right">
                            {t('deliberations.col_pass_rate')}
                        </th>
                        <th className="px-4 py-3" />
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {sheets.map((sheet) => (
                        <DeliberationRow key={sheet.exam_id} sheet={sheet} />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
