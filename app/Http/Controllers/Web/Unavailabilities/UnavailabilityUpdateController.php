<?php

namespace App\Http\Controllers\Web\Unavailabilities;

use App\Actions\Unavailabilities\UpdateUnavailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Unavailabilities\UpdateUnavailabilityRequest;
use App\Models\TeacherUnavailability;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UnavailabilityUpdateController extends Controller
{
    public function __invoke(
        UpdateUnavailabilityRequest $request,
        TeacherUnavailability $unavailability,
        UpdateUnavailabilityAction $action,
    ): RedirectResponse {
        $action->execute($unavailability, $request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.unavailability_updated'),
        ]);

        return back();
    }
}
