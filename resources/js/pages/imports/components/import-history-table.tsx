import { useMemo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { ImportHistoryRow } from './import-history-row';
import type { SpreadsheetImport } from './types';

interface ImportHistoryTableProps {
    imports: SpreadsheetImport[];
    selectedId: number | null;
    onToggle: (id: number) => void;
}

export function ImportHistoryTable({
    imports,
    selectedId,
    onToggle,
}: ImportHistoryTableProps) {
    const { t, locale } = useTranslation();
    const dateFormat = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                dateStyle: 'short',
                timeStyle: 'short',
            }),
        [locale],
    );

    return (
        <section className="flex flex-col gap-3">
            <div>
                <h2 className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                    {t('imports.history_title')}
                </h2>
                <p className="text-xs text-neutral-500">
                    {t('imports.history_desc')}
                </p>
            </div>

            {imports.length === 0 ? (
                <p className="rounded-lg border border-dashed border-neutral-300 p-6 text-center text-sm text-neutral-500 dark:border-neutral-700">
                    {t('imports.history_empty')}
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50">
                            <tr>
                                <th className="px-4 py-2">
                                    {t('imports.col_file')}
                                </th>
                                <th className="px-4 py-2">
                                    {t('imports.col_type')}
                                </th>
                                <th className="px-4 py-2">
                                    {t('common.status')}
                                </th>
                                <th className="px-4 py-2">
                                    {t('imports.col_result')}
                                </th>
                                <th className="px-4 py-2">
                                    {t('imports.col_uploaded_by')}
                                </th>
                                <th className="px-4 py-2">
                                    {t('imports.col_date')}
                                </th>
                                <th className="px-4 py-2" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                            {imports.map((record) => (
                                <ImportHistoryRow
                                    key={record.id}
                                    id={record.id}
                                    type={record.type}
                                    status={record.status}
                                    filename={record.original_filename}
                                    importedCount={record.imported_count}
                                    errorCount={record.error_count}
                                    uploadedBy={record.user?.name}
                                    uploadedAt={dateFormat.format(
                                        new Date(record.created_at),
                                    )}
                                    isSelected={record.id === selectedId}
                                    onToggle={onToggle}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}
