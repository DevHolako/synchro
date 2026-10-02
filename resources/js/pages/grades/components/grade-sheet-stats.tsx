import { useTranslation } from '@/i18n/LanguageContext';
import { computeFinalGrade, isComplete, isPassing } from './final-grade';
import { GradeFigure } from './grade-figure';
import type { GradeDraft, GradeRow } from './types';

interface GradeSheetStatsProps {
    drafts: ReadonlyArray<readonly [GradeRow, GradeDraft]>;
    continuousAssessmentWeight: number;
    /** The CC weight a line's completeness checks: 0 on a retake, whose CC is carried over. */
    completenessWeight: number;
}

/** Live figures over the grid as typed: complete lines, absences, average and passing finals. */
export function GradeSheetStats({
    drafts,
    continuousAssessmentWeight,
    completenessWeight,
}: GradeSheetStatsProps) {
    const { t } = useTranslation();
    let complete = 0;
    let absent = 0;
    let passing = 0;
    let total = 0;
    let finals = 0;

    for (const [row, draft] of drafts) {
        const final = computeFinalGrade(
            draft,
            continuousAssessmentWeight,
            row.previous_final_grade,
        );

        complete += isComplete(draft, completenessWeight) ? 1 : 0;
        absent += draft.absent ? 1 : 0;

        if (final !== null) {
            finals += 1;
            total += Number(final);
            passing += isPassing(final) ? 1 : 0;
        }
    }

    const figures = [
        [t('grades.stats_complete'), `${complete} / ${drafts.length}`],
        [t('grades.stats_absent'), String(absent)],
        [
            t('grades.stats_average'),
            finals === 0 ? '—' : (total / finals).toFixed(2),
        ],
        [t('grades.stats_passing'), `${passing} / ${finals}`],
    ];

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {figures.map(([label, value]) => (
                <GradeFigure key={label} label={label} value={value} />
            ))}
        </div>
    );
}
