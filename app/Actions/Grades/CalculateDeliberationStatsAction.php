<?php

namespace App\Actions\Grades;

use App\Models\ExamGrade;

/**
 * A grade sheet's figures for deliberation, over the lines that have a final grade: average and
 * median (rounded half up to two decimals), pass rate in percent, passing, failing and absent
 * counts. Computed on integer hundredths, like the final grades themselves.
 */
class CalculateDeliberationStatsAction
{
    /**
     * @param  list<array{final_grade: string|null, is_absent: bool}>  $lines
     * @return array{graded: int, average: string|null, median: string|null, pass_rate: string|null, passing: int, failing: int, absent: int}
     */
    public function execute(array $lines): array
    {
        $finals = [];
        $absent = 0;

        foreach ($lines as $line) {
            $absent += $line['is_absent'] ? 1 : 0;

            if ($line['final_grade'] !== null) {
                $finals[] = (int) round((float) $line['final_grade'] * 100);
            }
        }

        sort($finals);

        $graded = count($finals);
        $passMark = (int) round((float) ExamGrade::PASS_MARK * 100);
        $passing = count(array_filter($finals, fn (int $final): bool => $final >= $passMark));

        if ($graded === 0) {
            return ['graded' => 0, 'average' => null, 'median' => null, 'pass_rate' => null, 'passing' => 0, 'failing' => 0, 'absent' => $absent];
        }

        $middle = intdiv($graded, 2);
        $median = $graded % 2 === 1 ? $finals[$middle] * 2 : $finals[$middle - 1] + $finals[$middle];

        return [
            'graded' => $graded,
            'average' => $this->format($this->divideHalfUp(array_sum($finals), $graded)),
            'median' => $this->format($this->divideHalfUp($median, 2)),
            'pass_rate' => $this->format($this->divideHalfUp($passing * 10000, $graded)),
            'passing' => $passing,
            'failing' => $graded - $passing,
            'absent' => $absent,
        ];
    }

    private function divideHalfUp(int $dividend, int $divisor): int
    {
        return intdiv(2 * $dividend + $divisor, 2 * $divisor);
    }

    /**
     * Hundredths as a two-decimal string.
     */
    private function format(int $hundredths): string
    {
        return sprintf('%d.%02d', intdiv($hundredths, 100), $hundredths % 100);
    }
}
