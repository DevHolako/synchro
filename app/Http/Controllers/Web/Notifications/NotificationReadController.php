<?php

namespace App\Http\Controllers\Web\Notifications;

use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function __invoke(Request $request, string $id, MarkNotificationAsReadAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $marked = $action->execute($user, $id);

        return response()->json(['marked' => $marked]);
    }
}
