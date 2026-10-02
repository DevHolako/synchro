<?php

namespace App\Http\Controllers\Api\V1\CheckIn;

use App\Actions\Exams\ScanDoorCheckInAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScanCheckInController extends Controller
{
    public function __invoke(
        Request $request,
        ScanDoorCheckInAction $scanDoorCheckIn,
    ): JsonResponse {
        $raw = (string) ($request->input('uuid') ?? $request->input('token') ?? $request->input('qr_code') ?? '');

        if ($raw === '') {
            throw ValidationException::withMessages([
                'uuid' => [__('messages.qr_token_required')],
            ]);
        }

        $outcome = $scanDoorCheckIn->execute($raw, $request->user());

        return response()->json($outcome->toPayload(), $outcome->statusCode);
    }
}
