<?php

namespace App\Actions\Grades;

/**
 * The final grade out of 20 (spec 05): CC × w + exam × (100 − w), divided by 100 and rounded
 * half-up to two decimals. It runs on integer hundredths, so no float drift reaches the result.
 * An absent student's exam grade counts as 0; the CC share is kept. On a retake line the student
 * keeps the better of this and the normal session's final.
 */
class CalculateFinalGradeAction
{
    /**
     * @param  string|null  $continuousAssessmentGrade  Out of 20, at most two decimals.
     * @param  string|null  $examGrade  Out of 20, at most two decimals; ignored when absent.
     * @param  int  $continuousAssessmentWeight  The module's CC share, in percent.
     * @param  string|null  $previousFinalGrade  On a retake line, the normal session's final.
     * @return string|null The final grade with two decimals, or null while an input it needs is missing.
     */
    public function execute(?string $continuousAssessmentGrade, ?string $examGrade, bool $isAbsent, int $continuousAssessmentWeight, ?string $previousFinalGrade = null): ?string
    {
        if ($continuousAssessmentWeight > 0 && $continuousAssessmentGrade === null) {
            return null;
        }

        if (! $isAbsent && $examGrade === null) {
            return null;
        }

        $continuousAssessment = $continuousAssessmentWeight > 0 ? $this->hundredths((string) $continuousAssessmentGrade) : 0;
        $exam = $isAbsent ? 0 : $this->hundredths((string) $examGrade);

        // Weighted sum in hundredths × percent; adding 50 before the division rounds half up.
        $final = intdiv($continuousAssessment * $continuousAssessmentWeight + $exam * (100 - $continuousAssessmentWeight) + 50, 100);

        if ($previousFinalGrade !== null) {
            $final = max($final, $this->hundredths($previousFinalGrade));
        }

        return sprintf('%d.%02d', intdiv($final, 100), $final % 100);
    }

    private function hundredths(string $grade): int
    {
        return (int) round((float) $grade * 100);
    }
}
