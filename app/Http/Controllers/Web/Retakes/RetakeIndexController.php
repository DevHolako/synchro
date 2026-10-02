<?php

namespace App\Http\Controllers\Web\Retakes;

use App\Actions\Grades\ShowRetakeRosterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Grades\RetakeIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The retake roster: who failed each module, and the one-click retake exam.
 */
class RetakeIndexController extends Controller
{
    public function __invoke(RetakeIndexRequest $request, ShowRetakeRosterAction $action): Response
    {
        return Inertia::render('retakes/index', $action->execute($request->periodId()));
    }
}
