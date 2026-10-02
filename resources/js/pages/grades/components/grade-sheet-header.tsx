import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import type { GradeSheet, GradeSheetExam, GradeWeights } from './types';

const STATUS_CLASS: Record<GradeSheet['status'], string> = {
    draft: 'border-amber-300 text-amber-700 dark:border-amber-900 dark:text-amber-300',
    submitted:
        'border-sky-300 text-sky-700 dark:border-sky-900 dark:text-sky-300',
    locked: 'border-emerald-300 text-emerald-700 dark:border-emerald-900 dark:text-emerald-300',
};

interface GradeSheetHeaderProps {
    exam: GradeSheetExam;
    weights: GradeWeights;
    sheet: GradeSheet;
    editable: boolean;
}

export function GradeSheetHeader({
    exam,
    weights,
    sheet,
    editable,
}: GradeSheetHeaderProps) {
    const { t, locale } = useTranslation();

    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">
                    {t('grades.title')}
                </h1>
                <p className="text-sm text-neutral-500">
                    {exam.module} · {exam.period} ·{' '}
                    {formatDay(exam.start, locale, 'medium')} ·{' '}
                    {timeOf(exam.start)}–{timeOf(exam.end)}
                </p>
                <p className="text-sm text-neutral-500">
                    {t('grades.weights', {
                        cc: weights.continuous_assessment,
                        exam: weights.exam,
                    })}
                </p>
            </div>
            <div className="flex flex-col items-start gap-1 sm:items-end">
                <div className="flex gap-1">
                    <Badge
                        variant="outline"
                        className={STATUS_CLASS[sheet.status]}
                    >
                        {t(`grades.status_${sheet.status}`)}
                    </Badge>
                    {editable ? null : (
                        <Badge variant="outline">{t('grades.read_only')}</Badge>
                    )}
                </div>
                {sheet.submitted_at && sheet.submitted_by ? (
                    <span className="text-xs text-neutral-500">
                        {t('grades.submitted_on', {
                            date: `${formatDay(sheet.submitted_at, locale, 'medium')} ${timeOf(sheet.submitted_at)}`,
                            name: sheet.submitted_by,
                        })}
                    </span>
                ) : null}
            </div>
        </div>
    );
}
