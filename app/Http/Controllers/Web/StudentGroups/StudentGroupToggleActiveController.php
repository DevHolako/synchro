<?php

namespace App\Http\Controllers\Web\StudentGroups;

use App\Actions\StudentGroups\ToggleStudentGroupActiveAction;
use App\Http\Controllers\Controller;
use App\Models\StudentGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentGroupToggleActiveController extends Controller
{
    public function __invoke(Request $request, StudentGroup $studentGroup, ToggleStudentGroupActiveAction $action): RedirectResponse
    {
        if (! $request->user()?->can('update', $studentGroup)) {
            abort(403, 'Unauthorized to update student group status.');
        }

        $group = $action->execute($studentGroup);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Student Group {$group->name} ".($group->is_active ? 'activated' : 'deactivated').' successfully.',
        ]);

        return back();
    }
}
