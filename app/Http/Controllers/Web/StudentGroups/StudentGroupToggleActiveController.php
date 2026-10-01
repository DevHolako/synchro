<?php

namespace App\Http\Controllers\Web\StudentGroups;

use App\Actions\StudentGroups\ToggleStudentGroupActiveAction;
use App\Http\Controllers\Controller;
use App\Models\StudentGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StudentGroupToggleActiveController extends Controller
{
    public function __invoke(Request $request, StudentGroup $studentGroup, ToggleStudentGroupActiveAction $action): RedirectResponse
    {
        Gate::authorize('update', $studentGroup);

        $group = $action->execute($studentGroup);

        $status = $group->is_active ? __('messages.activated') : __('messages.deactivated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.student_group_status_updated', ['name' => $group->name, 'status' => $status]),
        ]);

        return back();
    }
}
