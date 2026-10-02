<?php

namespace App\Http\Controllers\Web\Notifications;

use App\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationReadAllController extends Controller
{
    public function __invoke(Request $request, MarkAllNotificationsAsReadAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $count = $action->execute($user);

        return response()->json(['count' => $count]);
    }
}
