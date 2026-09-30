<?php

namespace App\Http\Controllers\Web\Programs;

use App\Actions\Programs\UpdateProgramAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\UpdateProgramRequest;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProgramUpdateController extends Controller
{
    public function __invoke(UpdateProgramRequest $request, Program $program, UpdateProgramAction $action): RedirectResponse
    {
        $program = $action->execute($program, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Program {$program->name} updated successfully.",
        ]);

        return back();
    }
}
