<?php

namespace App\Http\Controllers\Web\Retakes;

use App\Actions\Grades\ShowRetakeRosterAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The retake roster: who failed each module, and the one-click retake exam.
 */
class RetakeIndexController extends Controller
{
    public function __invoke(Request $request, ShowRetakeRosterAction $action): Response
    {
        Gate::authorize('create', Exam::class);

        return Inertia::render('retakes/index', $action->execute($request->integer('period') ?: null));
    }
}
