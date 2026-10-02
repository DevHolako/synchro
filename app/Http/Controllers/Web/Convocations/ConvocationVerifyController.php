<?php

namespace App\Http\Controllers\Web\Convocations;

use App\Actions\Exams\ShowCheckInAction;
use App\Actions\Exams\ShowSupersededConvocationAction;
use App\Actions\Exams\VerifyConvocationAction;
use App\Http\Controllers\Controller;
use App\Models\SupersededConvocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The check-in screen a convocation's signed QR code opens at the exam door (ADR 0008), or a
 * warning when an emergency reschedule superseded that convocation (ADR 0005).
 */
class ConvocationVerifyController extends Controller
{
    public function __invoke(
        Request $request,
        string $uuid,
        VerifyConvocationAction $verify,
        ShowCheckInAction $showCheckIn,
        ShowSupersededConvocationAction $showSuperseded,
    ): Response {
        $convocation = $verify->execute($uuid);

        Gate::authorize('checkIn', $convocation->exam);

        return $convocation instanceof SupersededConvocation
            ? Inertia::render('convocations/superseded', $showSuperseded->execute($convocation))
            : Inertia::render('convocations/verify', $showCheckIn->execute($convocation, $request->user()));
    }
}
