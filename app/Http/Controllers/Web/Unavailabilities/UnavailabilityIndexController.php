<?php

namespace App\Http\Controllers\Web\Unavailabilities;

use App\Http\Controllers\Controller;
use App\Models\TeacherUnavailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher's own unavailabilities: current and upcoming by default, or past ones.
 */
class UnavailabilityIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('declare', TeacherUnavailability::class);

        $past = $request->string('period')->value() === 'past';
        $today = today()->toDateString();

        $unavailabilities = $request->user()->unavailabilities()
            ->with('reviewer:id,name')
            ->when(
                $past,
                fn (Builder $query) => $query->where('end_date', '<', $today),
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $today)
                ),
            )
            ->orderBy($past ? 'end_date' : 'start_date', $past ? 'desc' : 'asc')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('unavailabilities/index', [
            'unavailabilities' => $unavailabilities,
            'filters' => ['period' => $past ? 'past' : 'current'],
        ]);
    }
}
