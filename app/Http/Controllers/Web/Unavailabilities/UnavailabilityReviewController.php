<?php

namespace App\Http\Controllers\Web\Unavailabilities;

use App\Actions\Unavailabilities\ReviewUnavailabilityAction;
use App\Enums\UnavailabilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Unavailabilities\ReviewUnavailabilityRequest;
use App\Models\TeacherUnavailability;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UnavailabilityReviewController extends Controller
{
    public function __invoke(
        ReviewUnavailabilityRequest $request,
        TeacherUnavailability $unavailability,
        ReviewUnavailabilityAction $action,
    ): RedirectResponse {
        $decision = $request->decision();

        $unavailability = $action->execute($unavailability, $request->user(), $decision, $request->validated('review_note'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(
                $decision === UnavailabilityStatus::Approved ? 'messages.unavailability_approved' : 'messages.unavailability_rejected',
                ['teacher' => $unavailability->teacher->name],
            ),
        ]);

        return back();
    }
}
