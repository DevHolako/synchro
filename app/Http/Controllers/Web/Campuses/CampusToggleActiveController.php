<?php

namespace App\Http\Controllers\Web\Campuses;

use App\Actions\Campuses\ToggleCampusActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CampusToggleActiveController extends Controller
{
    public function __invoke(Request $request, Campus $campus, ToggleCampusActiveAction $action): RedirectResponse
    {
        Gate::authorize('update', $campus);

        $action->execute($campus);

        $status = $campus->is_active ? 'activated' : 'deactivated';

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Campus {$campus->name} {$status} successfully.",
        ]);

        return back();
    }
}
