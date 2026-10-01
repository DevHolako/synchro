import { Loader2 } from 'lucide-react';
import { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ImportStatus, SpreadsheetImport } from './types';

const STATUS_BADGE_CLASS: Record<ImportStatus, string> = {
    pending:
        'border-neutral-300 bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400',
    processing:
        'border-sky-500/30 bg-sky-50 text-sky-700 dark:bg-sky-950/20 dark:text-sky-400',
    succeeded:
        'border-emerald-500/30 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400',
    failed: 'border-rose-500/30 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400',
};

interface ImportHistoryRowProps {
    record: SpreadsheetImport;
    isSelected: boolean;
    onToggle: (id: number) => void;
}

export const ImportHistoryRow = memo(function ImportHistoryRow({
    record,
    isSelected,
    onToggle,
}: ImportHistoryRowProps) {
    const { t, locale } = useTranslation();
    const isActive =
        record.status === 'pending' || record.status === 'processing';

    return (
        <tr
            className={isSelected ? 'bg-neutral-50 dark:bg-neutral-800/40' : ''}
        >
            <td className="max-w-[240px] truncate px-4 py-3 font-medium text-neutral-900 dark:text-neutral-100">
                {record.original_filename}
            </td>
            <td className="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                {t(`imports.type_${record.type}`)}
            </td>
            <td className="px-4 py-3">
                <Badge
                    variant="outline"
                    className={STATUS_BADGE_CLASS[record.status]}
                >
                    {isActive && (
                        <Loader2 className="mr-1 size-3 animate-spin" />
                    )}
                    {t(`imports.status_${record.status}`)}
                </Badge>
            </td>
            <td className="px-4 py-3 text-xs text-neutral-600 dark:text-neutral-400">
                {record.status === 'succeeded' &&
                    t('imports.result_imported', {
                        count: record.imported_count,
                    })}
                {record.status === 'failed' &&
                    t('imports.result_errors', { count: record.error_count })}
            </td>
            <td className="px-4 py-3 text-xs text-neutral-600 dark:text-neutral-400">
                {record.user?.name}
            </td>
            <td className="px-4 py-3 text-xs whitespace-nowrap text-neutral-500">
                {new Intl.DateTimeFormat(locale, {
                    dateStyle: 'short',
                    timeStyle: 'short',
                }).format(new Date(record.created_at))}
            </td>
            <td className="px-4 py-3 text-right">
                {record.status === 'failed' && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onToggle(record.id)}
                    >
                        {isSelected
                            ? t('imports.hide_errors')
                            : t('imports.view_errors')}
                    </Button>
                )}
            </td>
        </tr>
    );
});
