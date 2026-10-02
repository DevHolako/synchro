<?php

namespace App\Http\Controllers\Api\V1\Schedules;

use App\Actions\CourseSessions\ListTimetableSessionsAction;
use App\Enums\TimetablePerspective;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseSessionResource;
use App\Models\CourseSession;
use App\Models\StudentGroup;
use App\Services\Scheduling\TimetableScope;
use App\Support\SchoolClock;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupScheduleController extends Controller
{
    public function __invoke(Request $request, int $id, ListTimetableSessionsAction $listSessions): AnonymousResourceCollection
    {
        $user = $request->user();

        $group = StudentGroup::findOrFail($id);

        $isMember = $user?->studentProfile?->student_group_id === $group->id;
        $canBrowse = $user?->can('browse', CourseSession::class) ?? false;

        if (! $isMember && ! $canBrowse) {
            throw new AuthorizationException;
        }

        $scope = TimetableScope::of(TimetablePerspective::Group, $group->id);
        [$from, $until] = $this->resolveDateRange($request);

        $sessions = $listSessions->execute($scope, $from, $until);

        return CourseSessionResource::collection($sessions);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveDateRange(Request $request): array
    {
        if ($request->filled('from') && $request->filled('until')) {
            return [
                CarbonImmutable::parse($request->string('from')->value()),
                CarbonImmutable::parse($request->string('until')->value()),
            ];
        }

        if ($request->filled('from')) {
            $from = CarbonImmutable::parse($request->string('from')->value());

            return [$from, $from->addDays(7)];
        }

        if ($request->filled('date')) {
            $anchor = CarbonImmutable::parse($request->string('date')->value());
            $from = $anchor->startOfWeek();

            return [$from, $from->addDays(7)];
        }

        $from = SchoolClock::today()->startOfWeek();

        return [$from, $from->addDays(7)];
    }
}
