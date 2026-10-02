<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\CheckInCandidateAction;
use App\Http\Controllers\Controller;
use App\Models\ExamCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CandidateCheckInStoreController extends Controller
{
    public function __invoke(Request $request, ExamCandidate $candidate, CheckInCandidateAction $action): RedirectResponse
    {
        Gate::authorize('checkIn', $candidate->exam);

        $action->execute($candidate, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_checked_in', ['name' => $candidate->student->name])]);

        return back();
    }
}
