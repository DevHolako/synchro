import { Link } from '@inertiajs/react';
import { memo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { show as showGrades } from '@/routes/exams/grades';
import { DeliberationStatusBadge } from './deliberation-status-badge';
import type { DeliberationSheet } from './types';

export const DeliberationRow = memo(function DeliberationRow({
    sheet,
}: {
    sheet: DeliberationSheet;
}) {
    const { t, locale } = useTranslation();

    return (
        <tr className="align-middle">
            <td className="px-4 py-3">
                <div className="font-medium">{sheet.module}</div>
                <div className="text-xs text-neutral-500">
                    {formatDay(sheet.start, locale, 'medium')} ·{' '}
                    {timeOf(sheet.start)}
                </div>
            </td>
            <td className="px-4 py-3">{sheet.teacher ?? '—'}</td>
            <td className="px-4 py-3">
                <DeliberationStatusBadge status={sheet.status} />
            </td>
            <td className="px-4 py-3 text-right font-mono">{sheet.lines}</td>
            <td className="px-4 py-3 text-right font-mono">
                {sheet.class_average ?? '—'}
            </td>
            <td className="px-4 py-3 text-right font-mono">
                {sheet.pass_rate === null ? '—' : `${sheet.pass_rate} %`}
            </td>
            <td className="px-4 py-3 text-right">
                {sheet.status === 'not_started' ? null : (
                    <Link
                        href={showGrades.url(sheet.exam_id)}
                        className="text-sm font-medium text-sky-700 hover:underline dark:text-sky-300"
                    >
                        {t('deliberations.open')}
                    </Link>
                )}
            </td>
        </tr>
    );
});
