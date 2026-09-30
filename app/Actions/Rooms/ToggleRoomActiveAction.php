<?php

namespace App\Actions\Rooms;

use App\Models\Room;

class ToggleRoomActiveAction
{
    public function execute(Room $room): Room
    {
        $room->update([
            'is_active' => ! $room->is_active,
        ]);

        return $room->refresh();
    }
}
