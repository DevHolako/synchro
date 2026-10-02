<?php

use App\Actions\Exams\SplitExamRoomsAction;
use Illuminate\Validation\ValidationException;

/**
 * @return list<array{id: int, last_name: string, first_name: string, student_number: string|null}>
 */
function candidates(int $count): array
{
    return array_map(fn (int $i): array => [
        'id' => $i,
        'last_name' => sprintf('Nom%03d', $i),
        'first_name' => fake()->firstName(),
        'student_number' => null,
    ], $count === 0 ? [] : range(1, $count));
}

/**
 * @return list<array{room_id: int, capacity: int}>
 */
function rooms(int ...$capacities): array
{
    return array_map(fn (int $capacity, int $index): array => ['room_id' => $index + 1, 'capacity' => $capacity], $capacities, array_keys($capacities));
}

/**
 * @return list<int>
 */
function sizes(array $split): array
{
    return array_map(fn (array $room): int => count($room['student_ids']), $split);
}

test('candidates are shared in proportion to each room exam capacity', function (int $count, array $capacities, array $expected) {
    expect(sizes(app(SplitExamRoomsAction::class)->execute(candidates($count), rooms(...$capacities), false)))->toBe($expected);
})->with([
    'even split of two equal rooms' => [50, [40, 40], [25, 25]],
    'an odd headcount gives the extra student to the first room' => [51, [40, 40], [26, 25]],
    'exactly full' => [40, [40], [40]],
    'three rooms' => [100, [40, 40, 30], [37, 36, 27]],
    'unequal rooms' => [30, [40, 10], [24, 6]],
    'full rooms are never overfilled' => [80, [40, 30, 10], [40, 30, 10]],
    'no candidates' => [0, [40, 40], [0, 0]],
]);

test('each room holds one unbroken alphabetical range, with its first and last surnames', function () {
    $split = app(SplitExamRoomsAction::class)->execute(array_reverse(candidates(5)), rooms(3, 3), false);

    expect($split[0])->toMatchArray(['student_ids' => [1, 2, 3], 'first_surname' => 'Nom001', 'last_surname' => 'Nom003'])
        ->and($split[1])->toMatchArray(['student_ids' => [4, 5], 'first_surname' => 'Nom004', 'last_surname' => 'Nom005']);
});

test('names sort the French way, ignoring accents and case, then by given name and student number', function () {
    $people = [
        ['id' => 1, 'last_name' => 'Ëzzine', 'first_name' => 'Ali', 'student_number' => null],
        ['id' => 2, 'last_name' => 'el Amrani', 'first_name' => 'Sara', 'student_number' => null],
        ['id' => 3, 'last_name' => 'Benali', 'first_name' => 'Youssef', 'student_number' => 'ETU-2'],
        ['id' => 4, 'last_name' => 'Alami', 'first_name' => 'Omar', 'student_number' => null],
        ['id' => 5, 'last_name' => 'BENALI', 'first_name' => 'Youssef', 'student_number' => 'ETU-1'],
        ['id' => 6, 'last_name' => 'Benali', 'first_name' => 'Amine', 'student_number' => null],
        ['id' => 7, 'last_name' => 'Ezzahra', 'first_name' => 'Nadia', 'student_number' => null],
    ];

    $split = app(SplitExamRoomsAction::class)->execute($people, rooms(10), false);

    expect($split[0]['student_ids'])->toBe([4, 6, 5, 3, 2, 7, 1]);
});

test('rooms that seat fewer than the candidates are refused', function () {
    expect(fn () => app(SplitExamRoomsAction::class)->execute(candidates(81), rooms(40, 40), false))
        ->toThrow(ValidationException::class);
});

test('a forced single room seats everyone whatever its capacity', function () {
    expect(sizes(app(SplitExamRoomsAction::class)->execute(candidates(60), rooms(30), true)))->toBe([60]);
});
