import type { GradeDraft, GradeRow } from './types';

/** Mirrors `ExamGrade::PASS_MARK`. */
const PASS_MARK = 10;
/** Mirrors `ExamGrade::MAX_GRADE`, in hundredths. */
const MAX_HUNDREDTHS = 2000;
const GRADE_PATTERN = /^\d{1,2}([.,]\d{1,2})?$/;

export type GradeInput =
    | { kind: 'empty' }
    | { kind: 'invalid' }
    | { kind: 'valid'; hundredths: number };

/** Reads a typed grade out of 20: "14,5" and "14.50" alike, at most two decimals. */
export function readGrade(value: string): GradeInput {
    const trimmed = value.trim();

    if (trimmed === '') {
        return { kind: 'empty' };
    }

    if (!GRADE_PATTERN.test(trimmed)) {
        return { kind: 'invalid' };
    }

    const hundredths = Math.round(
        Number.parseFloat(trimmed.replace(',', '.')) * 100,
    );

    return hundredths > MAX_HUNDREDTHS
        ? { kind: 'invalid' }
        : { kind: 'valid', hundredths };
}

/**
 * The final grade, as `CalculateFinalGradeAction` computes it on the server: CC × w + exam ×
 * (100 − w), over 100, rounded half up to two decimals on integer hundredths. Absent means an
 * exam grade of 0. Null while an input it needs is missing or invalid.
 */
export function computeFinalGrade(
    draft: GradeDraft,
    continuousAssessmentWeight: number,
): string | null {
    const continuousAssessment = readGrade(draft.continuousAssessment);
    const exam = readGrade(draft.exam);

    if (
        continuousAssessmentWeight > 0 &&
        continuousAssessment.kind !== 'valid'
    ) {
        return null;
    }

    if (!draft.absent && exam.kind !== 'valid') {
        return null;
    }

    const ccHundredths =
        continuousAssessment.kind === 'valid' && continuousAssessmentWeight > 0
            ? continuousAssessment.hundredths
            : 0;
    const examHundredths =
        draft.absent || exam.kind !== 'valid' ? 0 : exam.hundredths;
    const final = Math.floor(
        (ccHundredths * continuousAssessmentWeight +
            examHundredths * (100 - continuousAssessmentWeight) +
            50) /
            100,
    );

    return `${Math.floor(final / 100)}.${String(final % 100).padStart(2, '0')}`;
}

/** The line as typed, starting from what the server stored. */
export function draftOf(row: GradeRow): GradeDraft {
    return {
        continuousAssessment: row.continuous_assessment_grade ?? '',
        exam: row.exam_grade ?? '',
        absent: row.is_absent,
        remarks: row.remarks ?? '',
    };
}

/** Whether a typed grade field is in error (blank is allowed in a draft). */
export function isInvalid(value: string): boolean {
    return readGrade(value).kind === 'invalid';
}

/** Complete for submission, by the server's rule: CC when it counts, and an exam grade or a remarked absence. */
export function isComplete(
    draft: GradeDraft,
    continuousAssessmentWeight: number,
): boolean {
    if (
        continuousAssessmentWeight > 0 &&
        readGrade(draft.continuousAssessment).kind !== 'valid'
    ) {
        return false;
    }

    return draft.absent
        ? draft.remarks.trim() !== ''
        : readGrade(draft.exam).kind === 'valid';
}

/** Whether a final grade passes (at least 10/20). */
export function isPassing(final: string): boolean {
    return Number(final) >= PASS_MARK;
}
