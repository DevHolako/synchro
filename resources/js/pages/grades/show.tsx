import { Head } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import { useExamsBreadcrumbs } from '@/hooks/use-exams-breadcrumbs';
import { useTranslation } from '@/i18n/LanguageContext';
import { DeliberationPanel } from './components/deliberation-panel';
import { isComplete } from './components/final-grade';
import { GradeReturnBanner } from './components/grade-return-banner';
import { GradeSheetActions } from './components/grade-sheet-actions';
import { GradeSheetHeader } from './components/grade-sheet-header';
import { GradeSheetStats } from './components/grade-sheet-stats';
import { GradeSubmitDialog } from './components/grade-submit-dialog';
import { GradeTable } from './components/grade-table';
import type {
    Deliberation,
    GradeRow,
    GradeSheet,
    GradeSheetExam,
    GradeWeights,
} from './components/types';
import { useGradeSheet } from './components/use-grade-sheet';

interface GradesShowProps {
    exam: GradeSheetExam;
    weights: GradeWeights;
    sheet: GradeSheet;
    can_edit: boolean;
    rows: GradeRow[];
    deliberation: Deliberation;
}

/** An exam's grading grid: the module teacher's draft, read-only once submitted or for others. */
export default function GradesShow({
    exam,
    weights,
    sheet,
    can_edit: editable,
    rows,
    deliberation,
}: GradesShowProps) {
    const { t } = useTranslation();
    const [confirming, setConfirming] = useState(false);
    const weight = weights.continuous_assessment;
    // A retake's CC is carried over, not entered: only the retake itself must be complete.
    const completenessWeight = exam.retake ? 0 : weight;
    const grid = useGradeSheet(exam.id, rows, weight);
    const incompleteCount = useMemo(
        () =>
            grid.drafts.filter(
                ([, draft]) => !isComplete(draft, completenessWeight),
            ).length,
        [grid.drafts, completenessWeight],
    );
    const { submitSheet } = grid;
    const handleConfirm = useCallback(
        () => submitSheet(() => setConfirming(false)),
        [submitSheet],
    );

    useExamsBreadcrumbs();

    return (
        <>
            <Head title={t('grades.title')} />

            <div className="flex flex-col gap-4 p-6">
                <GradeSheetHeader
                    exam={exam}
                    weights={weights}
                    sheet={sheet}
                    editable={editable}
                />
                {sheet.status === 'draft' &&
                deliberation.returned_at &&
                deliberation.return_reason ? (
                    <GradeReturnBanner
                        returnedAt={deliberation.returned_at}
                        reason={deliberation.return_reason}
                    />
                ) : null}
                {editable ? (
                    <GradeSheetStats
                        drafts={grid.drafts}
                        continuousAssessmentWeight={weight}
                        completenessWeight={completenessWeight}
                    />
                ) : (
                    <DeliberationPanel
                        examId={exam.id}
                        retake={exam.retake}
                        deliberation={deliberation}
                    />
                )}
                <GradeTable
                    drafts={grid.drafts}
                    continuousAssessmentWeight={weight}
                    editable={editable}
                    retake={exam.retake}
                    onChange={grid.change}
                />
                {editable ? (
                    <GradeSheetActions
                        changedCount={grid.changedCount}
                        incompleteCount={incompleteCount}
                        hasInvalid={grid.hasInvalid}
                        processing={grid.processing}
                        onSave={grid.save}
                        onSubmit={() => setConfirming(true)}
                    />
                ) : null}
            </div>

            <GradeSubmitDialog
                open={confirming}
                processing={grid.processing}
                onOpenChange={setConfirming}
                onConfirm={handleConfirm}
            />
        </>
    );
}
