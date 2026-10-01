<?php

namespace App\Http\Controllers\Web\Programs;

use App\Actions\Programs\ToggleProgramActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProgramToggleActiveController extends Controller
{
    public function __invoke(Request $request, Program $program, ToggleProgramActiveAction $action): RedirectResponse
    {
        Gate::authorize('update', $program);

        $program = $action->execute($program);

        $status = $program->is_active ? __('messages.activated') : __('messages.deactivated');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.program_status_updated', ['name' => $program->name, 'status' => $status]),
        ]);

        return back();
    }
}
