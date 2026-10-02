import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { toastErrors } from '@/lib/toast-errors';
import { submit, update } from '@/routes/exams/grades';
import { draftOf, isInvalid } from './final-grade';
import { GRADE_SHEET_PROPS } from './grade-sheet-props';
import type { GradeDraft, GradeRow } from './types';

/**
 * The grid's typed lines: only the lines the teacher changed are kept here and sent on save,
 * so two open tabs never overwrite each other's lines. Leaving with unsaved lines asks first.
 */
export function useGradeSheet(
    examId: number,
    rows: GradeRow[],
    continuousAssessmentWeight: number,
) {
    const { t } = useTranslation();
    const [edits, setEdits] = useState<Record<number, GradeDraft>>({});
    const [processing, setProcessing] = useState(false);

    // One stored draft per saved line, rebuilt only when the server sends new lines, so the
    // memoized rows that were not edited keep the same object.
    const savedDrafts = useMemo(
        () => new Map(rows.map((row) => [row.student_id, draftOf(row)])),
        [rows],
    );
    const drafts = useMemo(
        () =>
            rows.map(
                (row) =>
                    [
                        row,
                        edits[row.student_id] ??
                            savedDrafts.get(row.student_id) ??
                            draftOf(row),
                    ] as const,
            ),
        [rows, edits, savedDrafts],
    );
    const changedCount = Object.keys(edits).length;
    const hasInvalid = Object.values(edits).some(
        (draft) =>
            isInvalid(draft.continuousAssessment) || isInvalid(draft.exam),
    );

    const change = useCallback(
        (studentId: number, patch: Partial<GradeDraft>) => {
            const saved = savedDrafts.get(studentId);

            if (saved === undefined) {
                return;
            }

            setEdits((previous) => ({
                ...previous,
                [studentId]: { ...(previous[studentId] ?? saved), ...patch },
            }));
        },
        [savedDrafts],
    );

    const save = useCallback(() => {
        const grades = Object.entries(edits).map(([studentId, draft]) => ({
            student_id: Number(studentId),
            continuous_assessment_grade:
                continuousAssessmentWeight > 0
                    ? draft.continuousAssessment.trim() || null
                    : null,
            exam_grade: draft.absent ? null : draft.exam.trim() || null,
            is_absent: draft.absent,
            remarks: draft.remarks.trim() || null,
        }));

        router.put(
            update.url(examId),
            { grades },
            {
                preserveScroll: true,
                only: GRADE_SHEET_PROPS,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => setEdits({}),
                onError: (errors) => toastErrors(errors),
            },
        );
    }, [edits, examId, continuousAssessmentWeight]);

    const submitSheet = useCallback(
        (onDone: () => void) =>
            router.post(
                submit.url(examId),
                {},
                {
                    preserveScroll: true,
                    only: GRADE_SHEET_PROPS,
                    onStart: () => setProcessing(true),
                    onFinish: () => {
                        setProcessing(false);
                        onDone();
                    },
                    onError: (errors) => toastErrors(errors),
                },
            ),
        [examId],
    );

    useEffect(() => {
        if (changedCount === 0) {
            return;
        }

        const warnOnUnload = (event: BeforeUnloadEvent) =>
            event.preventDefault();
        const removeVisitGuard = router.on(
            'before',
            (event) =>
                event.detail.visit.method !== 'get' ||
                window.confirm(t('grades.leave_confirm')),
        );

        window.addEventListener('beforeunload', warnOnUnload);

        return () => {
            window.removeEventListener('beforeunload', warnOnUnload);
            removeVisitGuard();
        };
    }, [changedCount, t]);

    return {
        drafts,
        changedCount,
        hasInvalid,
        processing,
        change,
        save,
        submitSheet,
    };
}
