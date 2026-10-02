import { ExamAllocationSheet } from './exam-allocation-sheet';
import { ExamConfirmDialog } from './exam-confirm-dialog';
import { ExamDialog } from './exam-dialog';
import { ExamPeriodDialog } from './exam-period-dialog';
import type { ExamOptions, ExamPeriod } from './types';
import type { useExamOverlays } from './use-exam-overlays';

interface ExamOverlaysProps {
    overlays: ReturnType<typeof useExamOverlays>;
    period: ExamPeriod | null;
    options: ExamOptions | null;
}

/** The exams page's dialogs and sheet; each form mounts per opening, so it starts fresh. */
export function ExamOverlays({ overlays, period, options }: ExamOverlaysProps) {
    return (
        <>
            {overlays.editingPeriod !== null ? (
                <ExamPeriodDialog
                    period={
                        overlays.editingPeriod === 'new'
                            ? null
                            : overlays.editingPeriod
                    }
                    onClose={overlays.closePeriod}
                />
            ) : null}
            {overlays.editingExam !== null && period && options ? (
                <ExamDialog
                    period={period}
                    exam={
                        overlays.editingExam === 'new'
                            ? null
                            : overlays.editingExam
                    }
                    options={options}
                    onClose={overlays.closeExam}
                />
            ) : null}
            {overlays.allocating ? (
                <ExamAllocationSheet
                    key={overlays.allocating.id}
                    exam={overlays.allocating}
                    onClose={overlays.closeAllocation}
                />
            ) : null}
            <ExamConfirmDialog
                confirmation={overlays.confirmation}
                onClose={overlays.closeConfirmation}
            />
        </>
    );
}
