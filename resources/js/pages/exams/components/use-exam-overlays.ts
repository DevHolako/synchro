import { useCallback, useState } from 'react';
import type { Exam, ExamConfirmation, ExamPeriod } from './types';

/** `null` while closed; `'new'` for a creation form; otherwise the record being edited. */
type Editing<T> = T | 'new' | null;

/** Which of the page's dialogs is open, and for what. */
export function useExamOverlays() {
    const [editingExam, setEditingExam] = useState<Editing<Exam>>(null);
    const [editingPeriod, setEditingPeriod] =
        useState<Editing<ExamPeriod>>(null);
    const [confirmation, setConfirmation] = useState<ExamConfirmation | null>(
        null,
    );
    const [allocating, setAllocating] = useState<Exam | null>(null);

    const editExam = useCallback((exam: Exam) => setEditingExam(exam), []);
    const allocate = useCallback((exam: Exam) => setAllocating(exam), []);
    const confirm = useCallback(
        (next: ExamConfirmation) => setConfirmation(next),
        [],
    );

    return {
        editingExam,
        editingPeriod,
        confirmation,
        allocating,
        editExam,
        allocate,
        closeAllocation: () => setAllocating(null),
        createExam: () => setEditingExam('new'),
        closeExam: () => setEditingExam(null),
        editPeriod: (period: ExamPeriod) => setEditingPeriod(period),
        createPeriod: () => setEditingPeriod('new'),
        closePeriod: () => setEditingPeriod(null),
        confirm,
        closeConfirmation: () => setConfirmation(null),
    };
}
