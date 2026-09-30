<?php

namespace App\Http\Controllers\Web\Buildings;

use App\Actions\Buildings\UpdateBuildingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buildings\UpdateBuildingRequest;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BuildingUpdateController extends Controller
{
    public function __invoke(UpdateBuildingRequest $request, Building $building, UpdateBuildingAction $action): RedirectResponse
    {
        $action->execute($building, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.building_updated', ['name' => $building->name]),
        ]);

        return back();
    }
}
