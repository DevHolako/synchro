<?php

namespace App\Http\Controllers\Web\Unavailabilities;

use App\Actions\Unavailabilities\DeleteUnavailabilityAction;
use App\Http\Controllers\Controller;
use App\Models\TeacherUnavailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UnavailabilityDestroyController extends Controller
{
    public function __invoke(TeacherUnavailability $unavailability, DeleteUnavailabilityAction $action): RedirectResponse
    {
        Gate::authorize('delete', $unavailability);

        $action->execute($unavailability);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.unavailability_deleted'),
        ]);

        return back();
    }
}
