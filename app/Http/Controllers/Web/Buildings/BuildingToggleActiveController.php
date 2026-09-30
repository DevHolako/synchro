<?php

namespace App\Http\Controllers\Web\Buildings;

use App\Actions\Buildings\ToggleBuildingActiveAction;
use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BuildingToggleActiveController extends Controller
{
    public function __invoke(Request $request, Building $building, ToggleBuildingActiveAction $action): RedirectResponse
    {
        Gate::authorize('update', $building);

        $action->execute($building);

        $status = $building->is_active ? 'activated' : 'deactivated';

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Building {$building->name} {$status} successfully.",
        ]);

        return back();
    }
}
