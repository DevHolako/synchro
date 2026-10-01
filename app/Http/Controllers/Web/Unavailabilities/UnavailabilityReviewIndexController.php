<?php

namespace App\Http\Controllers\Web\Unavailabilities;

use App\Enums\UnavailabilityStatus;
use App\Enums\UnavailabilityType;
use App\Http\Controllers\Controller;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every teacher's unavailabilities for coordinators to approve or reject; pending first by default.
 */
class UnavailabilityReviewIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', TeacherUnavailability::class);

        $status = $request->string('status')->value() === 'all'
            ? null
            : (UnavailabilityStatus::tryFrom($request->string('status')->value()) ?? UnavailabilityStatus::Pending);
        $type = UnavailabilityType::tryFrom($request->string('type')->value());
        $teacherId = $request->integer('teacher_id') ?: null;

        $unavailabilities = TeacherUnavailability::query()
            ->with(['teacher:id,name,email', 'reviewer:id,name'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($teacherId, fn (Builder $query) => $query->where('teacher_id', $teacherId))
            ->orderBy('created_at', $status === UnavailabilityStatus::Pending ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        $counts = TeacherUnavailability::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('unavailability-reviews/index', [
            'unavailabilities' => $unavailabilities,
            'teachers' => User::teachers()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status->value ?? 'all',
                'type' => $type->value ?? '',
                'teacher_id' => $teacherId ? (string) $teacherId : '',
            ],
            'stats' => [
                'pending' => (int) ($counts[UnavailabilityStatus::Pending->value] ?? 0),
                'approved' => (int) ($counts[UnavailabilityStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[UnavailabilityStatus::Rejected->value] ?? 0),
            ],
        ]);
    }
}
