<?php

namespace App\Http\Controllers\Web\Departments;

use App\Actions\Departments\UpdateDepartmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DepartmentUpdateController extends Controller
{
    public function __invoke(UpdateDepartmentRequest $request, Department $department, UpdateDepartmentAction $action): RedirectResponse
    {
        $department = $action->execute($department, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.department_updated', ['name' => $department->name]),
        ]);

        return back();
    }
}
