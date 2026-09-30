<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use InvalidArgumentException;

class CreateRoomAction
{
    /**
     * @param array{
     *     building_id: int,
     *     name: string,
     *     code?: string|null,
     *     floor?: int|null,
     *     course_capacity: int,
     *     exam_capacity: int,
     *     has_projector?: bool,
     *     is_lab?: bool,
     *     has_computers?: bool,
     *     has_sound_system?: bool,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): Room
    {
        if ($data['course_capacity'] <= 0) {
            throw new InvalidArgumentException('Course capacity must be greater than 0.');
        }

        if ($data['exam_capacity'] <= 0) {
            throw new InvalidArgumentException('Exam capacity must be greater than 0.');
        }

        if ($data['exam_capacity'] > $data['course_capacity']) {
            throw new InvalidArgumentException('Exam capacity cannot exceed course capacity.');
        }

        return Room::create([
            'building_id' => $data['building_id'],
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'floor' => $data['floor'] ?? null,
            'course_capacity' => (int) $data['course_capacity'],
            'exam_capacity' => (int) $data['exam_capacity'],
            'has_projector' => (bool) ($data['has_projector'] ?? false),
            'is_lab' => (bool) ($data['is_lab'] ?? false),
            'has_computers' => (bool) ($data['has_computers'] ?? false),
            'has_sound_system' => (bool) ($data['has_sound_system'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }
}
