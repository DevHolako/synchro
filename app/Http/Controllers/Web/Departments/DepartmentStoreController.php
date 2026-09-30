<?php

namespace App\Http\Controllers\Web\Departments;

use App\Actions\Departments\CreateDepartmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Departments\StoreDepartmentRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DepartmentStoreController extends Controller
{
    public function __invoke(StoreDepartmentRequest $request, CreateDepartmentAction $action): RedirectResponse
    {
        $department = $action->execute($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Department {$department->name} created successfully.",
        ]);

        return back();
    }
}
