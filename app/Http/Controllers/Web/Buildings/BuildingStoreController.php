<?php

namespace App\Http\Controllers\Web\Buildings;

use App\Actions\Buildings\CreateBuildingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buildings\StoreBuildingRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BuildingStoreController extends Controller
{
    public function __invoke(StoreBuildingRequest $request, CreateBuildingAction $action): RedirectResponse
    {
        $building = $action->execute($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Building {$building->name} created successfully.",
        ]);

        return back();
    }
}
