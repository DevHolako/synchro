<?php

namespace App\Http\Controllers\Web\Departments;

use App\Actions\Departments\ToggleDepartmentActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DepartmentToggleActiveController extends Controller
{
    public function __invoke(Request $request, Department $department, ToggleDepartmentActiveAction $action): RedirectResponse
    {
        Gate::authorize('update', $department);

        $department = $action->execute($department);

        $status = $department->is_active ? __('messages.activated') : __('messages.deactivated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.department_status_updated', ['name' => $department->name, 'status' => $status]),
        ]);

        return back();
    }
}
