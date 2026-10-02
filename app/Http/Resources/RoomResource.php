<?php

namespace App\Http\Resources;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Room $resource
 */
class RoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $room = $this->resource;

        return [
            'id' => $room->id,
            'name' => $room->name,
            'code' => $room->code,
            'floor' => $room->floor,
            'course_capacity' => $room->course_capacity,
            'exam_capacity' => $room->exam_capacity,
            'building' => $room->relationLoaded('building') && $room->building !== null
                ? [
                    'id' => $room->building->id,
                    'name' => $room->building->name,
                    'campus_id' => $room->building->campus_id,
                ]
                : null,
            'has_projector' => (bool) $room->has_projector,
            'is_lab' => (bool) $room->is_lab,
            'has_computers' => (bool) $room->has_computers,
            'has_sound_system' => (bool) $room->has_sound_system,
            'is_active' => (bool) $room->is_active,
        ];
    }
}
