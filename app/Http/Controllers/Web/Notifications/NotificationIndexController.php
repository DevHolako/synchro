<?php

namespace App\Http\Controllers\Web\Notifications;

use App\Actions\Notifications\ListRecentNotificationsAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationIndexController extends Controller
{
    public function __invoke(Request $request, ListRecentNotificationsAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $action->execute($user);

        return response()->json($data);
    }
}
