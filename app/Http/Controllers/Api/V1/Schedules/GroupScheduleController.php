<?php

namespace App\Http\Controllers\Api\V1\Schedules;

use App\Actions\CourseSessions\ListTimetableSessionsAction;
use App\Enums\TimetablePerspective;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseSessionResource;
use App\Models\CourseSession;
use App\Models\StudentGroup;
use App\Services\Scheduling\TimetableScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupScheduleController extends Controller
{
    use ResolvesDateRange;

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
}
