<?php

namespace App\Http\Controllers\Web\Convocations;

use App\Actions\Exams\ShowCheckInAction;
use App\Actions\Exams\VerifyConvocationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The check-in screen a convocation's signed QR code opens at the exam door (ADR 0008).
 */
class ConvocationVerifyController extends Controller
{
    public function __invoke(Request $request, string $uuid, VerifyConvocationAction $verify, ShowCheckInAction $show): Response
    {
        $candidate = $verify->execute($uuid);

        Gate::authorize('checkIn', $candidate->exam);

        return Inertia::render('convocations/verify', $show->execute($candidate, $request->user()));
    }
}
