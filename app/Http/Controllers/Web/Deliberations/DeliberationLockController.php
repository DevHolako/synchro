<?php

namespace App\Http\Controllers\Web\Deliberations;

use App\Actions\Grades\LockDeliberationAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DeliberationLockController extends Controller
{
    public function __invoke(Request $request, Exam $exam, LockDeliberationAction $action): RedirectResponse
    {
        Gate::authorize('lockGrades', $exam);

        $action->execute($exam, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.deliberation_locked')]);

        return back();
    }
}
