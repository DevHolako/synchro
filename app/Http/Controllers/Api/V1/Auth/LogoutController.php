<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RevokeApiTokenAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request, RevokeApiTokenAction $revokeToken): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $revokeToken->execute($user);
        }

        return response()->json([
            'message' => __('messages.logged_out'),
        ]);
    }
}
