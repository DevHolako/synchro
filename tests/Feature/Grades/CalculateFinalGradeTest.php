<?php

use App\Actions\Grades\CalculateFinalGradeAction;

test('the final grade weighs CC and exam, rounded half up to two decimals', function (?string $continuousAssessment, ?string $exam, bool $absent, int $weight, ?string $expected) {
    expect(app(CalculateFinalGradeAction::class)->execute($continuousAssessment, $exam, $absent, $weight))->toBe($expected);
})->with([
    '40% CC' => ['12.00', '15.00', false, 40, '13.80'],
    'all on the exam' => [null, '13.25', false, 0, '13.25'],
    'a half hundredth rounds up' => ['10.01', '10.02', false, 50, '10.02'],
    'below half a hundredth rounds down' => ['0.01', '0.00', false, 33, '0.00'],
    'no float drift' => ['0.29', '0.29', false, 30, '0.29'],
    'full marks' => ['20.00', '20.00', false, 99, '20.00'],
    'absent keeps the CC share' => ['14.00', null, true, 40, '5.60'],
    'absent with no CC' => [null, null, true, 0, '0.00'],
    'missing exam grade' => ['14.00', null, false, 40, null],
    'missing CC grade' => [null, '14.00', false, 40, null],
]);
