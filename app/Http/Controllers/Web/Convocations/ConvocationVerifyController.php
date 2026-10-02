<?php

namespace App\Http\Controllers\Web\Convocations;

use App\Actions\Exams\VerifyConvocationAction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page a convocation's QR code opens (ADR 0008): who the candidate is and where they sit.
 * The link is signed; the check-in itself comes with the mobile check-in screen.
 */
class ConvocationVerifyController extends Controller
{
    public function __invoke(string $uuid, VerifyConvocationAction $verify): Response
    {
        $candidate = $verify->execute($uuid);
        $exam = $candidate->exam;

        Gate::authorize('verifyConvocation', $exam);

        $profile = $candidate->student->studentProfile;

        return Inertia::render('convocations/verify', [
            'candidate' => [
                'name' => $candidate->student->name,
                'student_number' => $profile?->student_number,
                'group' => $profile?->studentGroup?->name,
                'room' => $candidate->roomAssignment->room->name,
                'building' => $candidate->roomAssignment->room->building->name,
                'seat' => $candidate->seat_number,
            ],
            'exam' => [
                'module' => "{$exam->module->code} · {$exam->module->name}",
                'start' => $exam->starts_at->format('Y-m-d\TH:i:s'),
                'end' => $exam->ends_at->format('Y-m-d\TH:i:s'),
                'state' => $exam->state->value,
            ],
        ]);
    }
}
