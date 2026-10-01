import { memo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ImportRowError } from './types';

const ImportErrorRow = memo(function ImportErrorRow({
    error,
}: {
    error: ImportRowError;
}) {
    const { t } = useTranslation();

    return (
        <tr>
            <td className="px-4 py-2 font-mono text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                {error.row}
            </td>
            <td className="px-4 py-2">
                {error.column ? (
                    <code className="font-mono text-xs text-rose-700 dark:text-rose-400">
                        {error.column}
                    </code>
                ) : (
                    <span className="text-xs text-neutral-400 italic">
                        {t('imports.whole_row')}
                    </span>
                )}
            </td>
            <td className="px-4 py-2 text-sm text-neutral-700 dark:text-neutral-300">
                {error.message}
            </td>
        </tr>
    );
});

export function ImportErrorsTable({ errors }: { errors: ImportRowError[] }) {
    const { t } = useTranslation();

    return (
        <div className="max-h-[420px] overflow-auto rounded-md border border-neutral-200 dark:border-neutral-800">
            <table className="w-full text-left text-sm">
                <thead className="sticky top-0 border-b border-neutral-200 bg-neutral-50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800">
                    <tr>
                        <th className="w-20 px-4 py-2">
                            {t('imports.col_row')}
                        </th>
                        <th className="w-44 px-4 py-2">
                            {t('imports.col_column')}
                        </th>
                        <th className="px-4 py-2">
                            {t('imports.col_message')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {errors.map((error, index) => (
                        <ImportErrorRow
                            key={`${error.row}-${error.column ?? ''}-${index}`}
                            error={error}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
