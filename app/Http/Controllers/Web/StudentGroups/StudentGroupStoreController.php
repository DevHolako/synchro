<?php

namespace App\Http\Controllers\Web\StudentGroups;

use App\Actions\StudentGroups\CreateStudentGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentGroups\StoreStudentGroupRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StudentGroupStoreController extends Controller
{
    public function __invoke(StoreStudentGroupRequest $request, CreateStudentGroupAction $action): RedirectResponse
    {
        $group = $action->execute($request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.student_group_created', ['name' => $group->name]),
        ]);

        return back();
    }
}
