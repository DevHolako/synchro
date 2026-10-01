<?php

namespace App\Http\Controllers\Web\Unavailabilities;

use App\Actions\Unavailabilities\DeclareUnavailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Unavailabilities\StoreUnavailabilityRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UnavailabilityStoreController extends Controller
{
    public function __invoke(StoreUnavailabilityRequest $request, DeclareUnavailabilityAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.unavailability_declared'),
        ]);

        return back();
    }
}
