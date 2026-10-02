<?php

namespace App\Actions\Exams;

use App\Support\FrenchCollation;
use Illuminate\Validation\ValidationException;

/**
 * Divides an exam's candidates across its rooms (spec 04): sorted by surname, then given name,
 * French collation (accents and case ignored), and student number for exact ties; each room
 * gets one unbroken alphabetical range, sized in proportion to its exam capacity.
 */
class SplitExamRoomsAction
{
    /**
     * @param  list<array{id: int, last_name: string, first_name: string, student_number: string|null}>  $candidates
     * @param  list<array{room_id: int, capacity: int}>  $rooms  In the coordinator's order.
     * @param  bool  $forceSingleRoom  Everyone in the first room, whatever its capacity.
     * @return list<array{room_id: int, student_ids: list<int>, first_surname: string|null, last_surname: string|null}>
     *
     * @throws ValidationException When the rooms seat fewer than the candidates.
     */
    public function execute(array $candidates, array $rooms, bool $forceSingleRoom): array
    {
        $sorted = $this->sorted($candidates);
        $sizes = $forceSingleRoom
            ? [count($sorted), ...array_fill(0, max(0, count($rooms) - 1), 0)]
            : $this->sizes(count($sorted), array_column($rooms, 'capacity'));

        $split = [];
        $offset = 0;

        foreach ($rooms as $index => $room) {
            $students = array_slice($sorted, $offset, $sizes[$index]);
            $offset += $sizes[$index];

            $split[] = [
                'room_id' => $room['room_id'],
                'student_ids' => array_column($students, 'id'),
                'first_surname' => $students === [] ? null : $students[0]['last_name'],
                'last_surname' => $students === [] ? null : $students[count($students) - 1]['last_name'],
            ];
        }

        return $split;
    }

    /**
     * Room sizes in proportion to capacity, by largest remainder: the floors first, then one more
     * student for the rooms with the largest fractional share (earlier rooms win ties). A room
     * whose share is fractional is below its capacity, so no room is ever overfilled.
     *
     * @param  list<int>  $capacities
     * @return list<int>
     */
    private function sizes(int $count, array $capacities): array
    {
        $seats = array_sum($capacities);

        if ($seats < $count) {
            throw ValidationException::withMessages(['room_ids' => __('messages.exam_rooms_short', [
                'candidates' => $count,
                'seats' => $seats,
            ])]);
        }

        if ($count === 0) {
            return array_fill(0, count($capacities), 0);
        }

        $sizes = [];
        $remainders = [];

        foreach ($capacities as $index => $capacity) {
            $share = $count * $capacity / $seats;
            $sizes[$index] = (int) floor($share);
            $remainders[$index] = $share - $sizes[$index];
        }

        // Stable sort: equal remainders keep the coordinator's order.
        uasort($remainders, fn (float $a, float $b): int => $b <=> $a);

        foreach (array_slice(array_keys($remainders), 0, $count - array_sum($sizes)) as $index) {
            $sizes[$index]++;
        }

        return array_values($sizes);
    }

    /**
     * @param  list<array{id: int, last_name: string, first_name: string, student_number: string|null}>  $candidates
     * @return list<array{id: int, last_name: string, first_name: string, student_number: string|null}>
     */
    private function sorted(array $candidates): array
    {
        $collator = FrenchCollation::collator();

        usort($candidates, fn (array $a, array $b): int => $collator->compare($a['last_name'], $b['last_name'])
            ?: $collator->compare($a['first_name'], $b['first_name'])
            ?: strcmp((string) $a['student_number'], (string) $b['student_number'])
            ?: $a['id'] <=> $b['id']);

        return $candidates;
    }
}
