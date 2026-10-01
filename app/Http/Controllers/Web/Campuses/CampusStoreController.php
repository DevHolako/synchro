<?php

namespace App\Http\Controllers\Web\Campuses;

use App\Actions\Campuses\CreateCampusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campuses\StoreCampusRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CampusStoreController extends Controller
{
    public function __invoke(StoreCampusRequest $request, CreateCampusAction $action): RedirectResponse
    {
        $campus = $action->execute($request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.campus_created', ['name' => $campus->name]),
        ]);

        return back();
    }
}
