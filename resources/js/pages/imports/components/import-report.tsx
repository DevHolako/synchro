import { CheckCircle2, XCircle } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { ImportErrorsTable } from './import-errors-table';
import type { ImportReport as ImportReportData } from './types';

export function ImportReport({ report }: { report: ImportReportData }) {
    const { t } = useTranslation();

    if (report.status === 'succeeded') {
        return (
            <div className="flex items-start gap-3 rounded-lg border border-emerald-500/30 bg-emerald-50 p-4 dark:bg-emerald-950/20">
                <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-600" />
                <div>
                    <h2 className="text-sm font-semibold text-emerald-900 dark:text-emerald-300">
                        {t('imports.report_success_title')}
                    </h2>
                    <p className="text-sm text-emerald-800 dark:text-emerald-400">
                        {t('imports.report_success_desc', {
                            count: report.imported,
                            file: report.file,
                        })}
                    </p>
                </div>
            </div>
        );
    }

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
                            total: report.total_errors,
                            file: report.file,
                        })}
                    </p>
                    {report.total_errors > report.errors.length && (
                        <p className="text-xs text-rose-700 dark:text-rose-400">
                            {t('imports.report_truncated', {
                                shown: report.errors.length,
                                total: report.total_errors,
                            })}
                        </p>
                    )}
                </div>
            </div>
            <ImportErrorsTable errors={report.errors} />
        </div>
    );
}
