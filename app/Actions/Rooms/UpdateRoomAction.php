<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use InvalidArgumentException;

class UpdateRoomAction
{
    /**
     * @param array{
     *     building_id?: int,
     *     name?: string,
     *     code?: string|null,
     *     floor?: int|null,
     *     course_capacity?: int,
     *     exam_capacity?: int,
     *     has_projector?: bool,
     *     is_lab?: bool,
     *     has_computers?: bool,
     *     has_sound_system?: bool,
     *     is_active?: bool
     * } $data
     */
    public function execute(Room $room, array $data): Room
    {
        $courseCapacity = $data['course_capacity'] ?? $room->course_capacity;
        $examCapacity = $data['exam_capacity'] ?? $room->exam_capacity;

        if ($courseCapacity <= 0) {
            throw new InvalidArgumentException('Course capacity must be greater than 0.');
        }

        if ($examCapacity <= 0) {
            throw new InvalidArgumentException('Exam capacity must be greater than 0.');
        }

        if ($examCapacity > $courseCapacity) {
            throw new InvalidArgumentException('Exam capacity cannot exceed course capacity.');
        }

        $room->update($data);

        return $room->refresh();
    }
}
