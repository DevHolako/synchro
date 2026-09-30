<?php

namespace App\Http\Controllers\Web\Rooms;

use App\Actions\Rooms\ToggleRoomActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RoomToggleActiveController extends Controller
{
    public function __invoke(Request $request, Room $room, ToggleRoomActiveAction $action): RedirectResponse
    {
        Gate::authorize('update', $room);

        $action->execute($room);

        $status = $room->is_active ? 'activated' : 'deactivated';

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Room {$room->name} {$status} successfully.",
        ]);

        return back();
    }
}
