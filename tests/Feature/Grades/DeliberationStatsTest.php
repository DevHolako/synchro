<?php

use App\Actions\Grades\CalculateDeliberationStatsAction;

/**
 * @param  list<string|null>  $finals
 * @return list<array{final_grade: string|null, is_absent: bool}>
 */
function deliberationLines(array $finals, int $absent = 0): array
{
    return array_map(fn (?string $final, int $index): array => [
        'final_grade' => $final,
        'is_absent' => $index < $absent,
    ], $finals, array_keys($finals));
}

test('deliberation figures are computed on hundredths and rounded half up', function () {
    expect(app(CalculateDeliberationStatsAction::class)->execute(deliberationLines(['12.00', '8.50', '15.25'], absent: 1)))
        ->toBe([
            'graded' => 3,
            'average' => '11.92',
            'median' => '12.00',
            'pass_rate' => '66.67',
            'passing' => 2,
            'failing' => 1,
            'absent' => 1,
        ]);
});

test('an even count takes the middle pair for the median, and lines without a final are left out', function () {
    $stats = app(CalculateDeliberationStatsAction::class)->execute(deliberationLines(['10.00', '9.99', '10.01', '4.00', null]));

    expect($stats['graded'])->toBe(4)
        ->and($stats['median'])->toBe('10.00')
        ->and($stats['pass_rate'])->toBe('50.00');
});

test('a sheet without finals has no figures', function () {
    expect(app(CalculateDeliberationStatsAction::class)->execute([]))
        ->toMatchArray(['graded' => 0, 'average' => null, 'median' => null, 'pass_rate' => null]);
});
