import { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/LanguageContext';
import type { OwnGrade } from './types';

export const OwnGradeRow = memo(function OwnGradeRow({
    grade,
}: {
    grade: OwnGrade;
}) {
    const { t } = useTranslation();
    const weight = grade.continuous_assessment_weight;

    return (
        <tr className="align-middle">
            <td className="px-4 py-3">
                <div className="font-medium">{grade.module}</div>
                <div className="text-xs text-neutral-500">
                    {t('my_grades.weighting', {
                        cc: weight,
                        exam: 100 - weight,
                    })}
                </div>
            </td>
            <td className="px-4 py-3 text-neutral-600 dark:text-neutral-300">
                {grade.period} · {grade.academic_year}
                {grade.session_type === 'rattrapage' ? (
                    <Badge variant="outline" className="ml-2">
                        {t('my_grades.session_retake')}
                    </Badge>
                ) : null}
            </td>
            <td className="px-4 py-3 text-right font-mono">
                {weight > 0 ? (grade.continuous_assessment_grade ?? '—') : '—'}
            </td>
            <td className="px-4 py-3 text-right font-mono">
                {grade.is_absent
                    ? t('my_grades.absent')
                    : (grade.exam_grade ?? '—')}
            </td>
            <td className="px-4 py-3 text-right font-mono font-semibold">
                {grade.final_grade ?? '—'}
            </td>
            <td className="px-4 py-3 text-right">
                <Badge
                    variant="outline"
                    className={
                        grade.passed
                            ? 'border-emerald-300 text-emerald-700 dark:border-emerald-900 dark:text-emerald-300'
                            : 'border-rose-300 text-rose-700 dark:border-rose-900 dark:text-rose-300'
                    }
                >
                    {t(
                        grade.passed
                            ? 'my_grades.passed'
                            : grade.session_type === 'rattrapage'
                              ? 'my_grades.failed'
                              : 'my_grades.retake',
                    )}
                </Badge>
            </td>
        </tr>
    );
});
