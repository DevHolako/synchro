import { XCircle } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { ImportErrorsTable } from './import-errors-table';
import type { ImportFailureReport } from './types';

export function ImportReport({ report }: { report: ImportFailureReport }) {
    const { t } = useTranslation();

    const errors = report.errors ?? [];

    return (
        <div className="flex flex-col gap-3 rounded-lg border border-rose-500/30 bg-rose-50/60 p-4 dark:bg-rose-950/20">
            <div className="flex items-start gap-3">
                <XCircle className="mt-0.5 size-5 shrink-0 text-rose-600" />
                <div>
                    <h2 className="text-sm font-semibold text-rose-900 dark:text-rose-300">
                        {t('imports.report_failed_title')}
                    </h2>
                    <p className="text-sm text-rose-800 dark:text-rose-400">
                        {t('imports.report_failed_desc', {
                            total: report.error_count,
                            file: report.original_filename,
                        })}
                    </p>
                    {report.error_count > errors.length && (
                        <p className="text-xs text-rose-700 dark:text-rose-400">
                            {t('imports.report_truncated', {
                                shown: errors.length,
                                total: report.error_count,
                            })}
                        </p>
                    )}
                </div>
            </div>
            <ImportErrorsTable errors={errors} />
        </div>
    );
}
