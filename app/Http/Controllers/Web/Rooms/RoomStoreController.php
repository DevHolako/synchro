<?php

namespace App\Http\Controllers\Web\Rooms;

use App\Actions\Rooms\CreateRoomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rooms\StoreRoomRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RoomStoreController extends Controller
{
    public function __invoke(StoreRoomRequest $request, CreateRoomAction $action): RedirectResponse
    {
        $room = $action->execute($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.room_created', ['name' => $room->name]),
        ]);

        return back();
    }
}
