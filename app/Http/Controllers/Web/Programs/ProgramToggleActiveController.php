<?php

namespace App\Http\Controllers\Web\Programs;

use App\Actions\Programs\ToggleProgramActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramToggleActiveController extends Controller
{
    public function __invoke(Request $request, Program $program, ToggleProgramActiveAction $action): RedirectResponse
    {
        if (! $request->user()?->can('update', $program)) {
            abort(403, 'Unauthorized to update program status.');
        }

        $program = $action->execute($program);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Program {$program->name} ".($program->is_active ? 'activated' : 'deactivated').' successfully.',
        ]);

        return back();
    }
}
