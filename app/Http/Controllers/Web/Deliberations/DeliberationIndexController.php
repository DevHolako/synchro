<?php

namespace App\Http\Controllers\Web\Deliberations;

use App\Actions\Grades\ListDeliberationsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Grades\DeliberationIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The coordinators' deliberation board: where each finished exam's grade sheet stands.
 */
class DeliberationIndexController extends Controller
{
    public function __invoke(DeliberationIndexRequest $request, ListDeliberationsAction $action): Response
    {
        return Inertia::render('deliberations/index', [
            ...$action->execute($request->periodId(), $request->status()),
            'filters' => ['status' => $request->status() ?? ''],
        ]);
    }
}
