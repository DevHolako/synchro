<?php

namespace App\Actions\Exams;

use App\Models\Exam;

/**
 * Sets an exam's rooms to the given order: rooms dropped lose their assignment (and
 * invigilators), rooms kept keep theirs with a new position, new rooms are added.
 */
class SyncExamRoomsAction
{
    /**
     * @param  list<int>  $roomIds  In the coordinator's order.
     */
    public function execute(Exam $exam, array $roomIds): void
    {
        $exam->roomAssignments()->whereNotIn('room_id', $roomIds)->delete();

        foreach ($roomIds as $position => $roomId) {
            $exam->roomAssignments()->updateOrCreate(['room_id' => $roomId], ['position' => $position]);
        }
    }
}
