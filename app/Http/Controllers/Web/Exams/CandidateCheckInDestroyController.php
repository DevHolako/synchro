<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\UndoCheckInAction;
use App\Http\Controllers\Controller;
use App\Models\ExamCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CandidateCheckInDestroyController extends Controller
{
    public function __invoke(Request $request, ExamCandidate $candidate, UndoCheckInAction $action): RedirectResponse
    {
        Gate::authorize('checkIn', $candidate->exam);

        $action->execute($candidate, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_check_in_undone', ['name' => $candidate->student->officialName()])]);

        return back();
    }
}
