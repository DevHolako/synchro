import { memo } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/i18n/LanguageContext';
import { computeFinalGrade, isInvalid, isPassing } from './final-grade';
import type { GradeDraft, GradeRow as GradeRowData } from './types';

const GRADE_INPUT_CLASS = 'w-20 text-right font-mono';
const INVALID_CLASS = 'border-red-500 focus-visible:ring-red-500';

interface GradeRowProps {
    row: GradeRowData;
    draft: GradeDraft;
    continuousAssessmentWeight: number;
    editable: boolean;
    retake: boolean;
    onChange: (studentId: number, patch: Partial<GradeDraft>) => void;
}

export const GradeRow = memo(function GradeRow({
    row,
    draft,
    continuousAssessmentWeight,
    editable,
    retake,
    onChange,
}: GradeRowProps) {
    const { t } = useTranslation();
    const initials = useInitials();
    const final = computeFinalGrade(
        draft,
        continuousAssessmentWeight,
        row.previous_final_grade,
    );
    const remarkMissing = draft.absent && draft.remarks.trim() === '';
    const id = row.student_id;

    return (
        <tr className="align-middle">
            <td className="px-4 py-2">
                <div className="flex items-center gap-3">
                    <Avatar className="size-8">
                        <AvatarFallback className="text-xs font-semibold">
                            {initials(row.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <div className="truncate font-medium">{row.name}</div>
                        <div className="text-xs text-neutral-500">
                            {row.student_number ?? '—'}
                            {row.group ? ` · ${row.group}` : ''}
                        </div>
                    </div>
                </div>
            </td>
            {continuousAssessmentWeight > 0 ? (
                <td className="px-4 py-2">
                    <Input
                        inputMode="decimal"
                        aria-label={t('grades.col_cc')}
                        aria-invalid={isInvalid(draft.continuousAssessment)}
                        title={
                            retake
                                ? t('grades.cc_carried')
                                : isInvalid(draft.continuousAssessment)
                                  ? t('grades.invalid')
                                  : undefined
                        }
                        className={`${GRADE_INPUT_CLASS} ${isInvalid(draft.continuousAssessment) ? INVALID_CLASS : ''}`}
                        value={draft.continuousAssessment}
                        disabled={!editable || retake}
                        onChange={(event) =>
                            onChange(id, {
                                continuousAssessment: event.target.value,
                            })
                        }
                    />
                </td>
            ) : null}
            <td className="px-4 py-2">
                {draft.absent ? (
                    <span className="inline-flex w-20 justify-end font-mono text-sm font-semibold text-rose-600">
                        {t('grades.absent_short')}
                    </span>
                ) : (
                    <Input
                        inputMode="decimal"
                        aria-label={t('grades.col_exam')}
                        aria-invalid={isInvalid(draft.exam)}
                        title={
                            isInvalid(draft.exam)
                                ? t('grades.invalid')
                                : undefined
                        }
                        className={`${GRADE_INPUT_CLASS} ${isInvalid(draft.exam) ? INVALID_CLASS : ''}`}
                        value={draft.exam}
                        disabled={!editable}
                        onChange={(event) =>
                            onChange(id, { exam: event.target.value })
                        }
                    />
                )}
            </td>
            <td className="px-4 py-2 text-center">
                <Checkbox
                    aria-label={t('grades.col_absent')}
                    checked={draft.absent}
                    disabled={!editable}
                    onCheckedChange={(checked) =>
                        onChange(id, { absent: checked === true })
                    }
                />
            </td>
            <td className="px-4 py-2">
                <Input
                    aria-label={t('grades.col_remarks')}
                    maxLength={255}
                    placeholder={t(
                        remarkMissing
                            ? 'grades.remarks_required'
                            : 'grades.remarks_placeholder',
                    )}
                    className={remarkMissing ? 'border-amber-500' : ''}
                    value={draft.remarks}
                    disabled={!editable}
                    onChange={(event) =>
                        onChange(id, { remarks: event.target.value })
                    }
                />
            </td>
            <td className="px-4 py-2 text-right font-mono font-semibold">
                {final === null ? (
                    <span className="text-neutral-400">—</span>
                ) : (
                    <span
                        className={
                            isPassing(final)
                                ? 'text-emerald-600'
                                : 'text-rose-600'
                        }
                    >
                        {final}
                    </span>
                )}
                {row.previous_final_grade !== null ? (
                    <div className="text-xs font-normal text-neutral-500">
                        {t('grades.normal_final', {
                            grade: row.previous_final_grade,
                        })}
                    </div>
                ) : null}
            </td>
        </tr>
    );
});
