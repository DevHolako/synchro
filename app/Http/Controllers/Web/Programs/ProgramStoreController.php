<?php

namespace App\Http\Controllers\Web\Programs;

use App\Actions\Programs\CreateProgramAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\StoreProgramRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProgramStoreController extends Controller
{
    public function __invoke(StoreProgramRequest $request, CreateProgramAction $action): RedirectResponse
    {
        $program = $action->execute($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Program {$program->name} created successfully.",
        ]);

        return back();
    }
}
