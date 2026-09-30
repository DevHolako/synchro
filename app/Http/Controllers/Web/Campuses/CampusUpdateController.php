<?php

namespace App\Http\Controllers\Web\Campuses;

use App\Actions\Campuses\UpdateCampusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campuses\UpdateCampusRequest;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CampusUpdateController extends Controller
{
    public function __invoke(UpdateCampusRequest $request, Campus $campus, UpdateCampusAction $action): RedirectResponse
    {
        $action->execute($campus, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Campus {$campus->name} updated successfully.",
        ]);

        return back();
    }
}
