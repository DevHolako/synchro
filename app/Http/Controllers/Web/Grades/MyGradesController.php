<?php

namespace App\Http\Controllers\Web\Grades;

use App\Actions\Grades\ListOwnGradesAction;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A student's published grades: lines of locked deliberations only.
 */
class MyGradesController extends Controller
{
    public function __invoke(Request $request, ListOwnGradesAction $action): Response
    {
        Gate::authorize(Permission::ViewOwnGrades->value);

        return Inertia::render('my-grades/index', [
            'grades' => $action->execute($request->user()),
        ]);
    }
}
