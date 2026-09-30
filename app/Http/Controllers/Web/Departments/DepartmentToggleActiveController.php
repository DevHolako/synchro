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

        $status = $department->is_active ? __('messages.activated') : __('messages.deactivated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.department_status_updated', ['name' => $department->name, 'status' => $status]),
        ]);

        return back();
    }
}
