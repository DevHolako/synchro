import { Head } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import { useExamsBreadcrumbs } from '@/hooks/use-exams-breadcrumbs';
import { useTranslation } from '@/i18n/LanguageContext';
import { isComplete } from './components/final-grade';
import { GradeSheetActions } from './components/grade-sheet-actions';
import { GradeSheetHeader } from './components/grade-sheet-header';
import { GradeSheetStats } from './components/grade-sheet-stats';
import { GradeSubmitDialog } from './components/grade-submit-dialog';
import { GradeTable } from './components/grade-table';
import type {
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
}

/** An exam's grading grid: the module teacher's draft, read-only once submitted or for others. */
export default function GradesShow({
    exam,
    weights,
    sheet,
    can_edit: editable,
    rows,
}: GradesShowProps) {
    const { t } = useTranslation();
    const [confirming, setConfirming] = useState(false);
    const weight = weights.continuous_assessment;
    const grid = useGradeSheet(exam.id, rows, weight);
    const incompleteCount = useMemo(
        () =>
            grid.drafts.filter(([, draft]) => !isComplete(draft, weight))
                .length,
        [grid.drafts, weight],
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
                <GradeSheetStats
                    drafts={grid.drafts}
                    continuousAssessmentWeight={weight}
                />
                <GradeTable
                    drafts={grid.drafts}
                    continuousAssessmentWeight={weight}
                    editable={editable}
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
