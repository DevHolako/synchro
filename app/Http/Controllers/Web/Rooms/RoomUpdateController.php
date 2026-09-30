<?php

namespace App\Http\Controllers\Web\Rooms;

use App\Actions\Rooms\UpdateRoomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rooms\UpdateRoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RoomUpdateController extends Controller
{
    public function __invoke(UpdateRoomRequest $request, Room $room, UpdateRoomAction $action): RedirectResponse
    {
        $action->execute($room, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.room_updated', ['name' => $room->name]),
        ]);

        return back();
    }
}
