<?php

namespace App\Http\Controllers\Web\Departments;

use App\Actions\Departments\ToggleDepartmentActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentToggleActiveController extends Controller
{
    public function __invoke(Request $request, Department $department, ToggleDepartmentActiveAction $action): RedirectResponse
    {
        if (! $request->user()?->can('update', $department)) {
            abort(403, 'Unauthorized to update department status.');
        }

        $department = $action->execute($department);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Department {$department->name} ".($department->is_active ? 'activated' : 'deactivated').' successfully.',
        ]);

        return back();
    }
}
