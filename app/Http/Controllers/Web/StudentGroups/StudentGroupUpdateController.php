<?php

namespace App\Http\Controllers\Web\StudentGroups;

use App\Actions\StudentGroups\UpdateStudentGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentGroups\UpdateStudentGroupRequest;
use App\Models\StudentGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StudentGroupUpdateController extends Controller
{
    public function __invoke(UpdateStudentGroupRequest $request, StudentGroup $studentGroup, UpdateStudentGroupAction $action): RedirectResponse
    {
        $group = $action->execute($studentGroup, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Student Group {$group->name} updated successfully.",
        ]);

        return back();
    }
}
