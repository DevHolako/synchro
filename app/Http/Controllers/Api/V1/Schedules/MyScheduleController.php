<?php

namespace App\Http\Controllers\Api\V1\Schedules;

use App\Actions\CourseSessions\ListTimetableSessionsAction;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseSessionResource;
use App\Models\CourseSession;
use App\Services\Scheduling\TimetableScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyScheduleController extends Controller
{
    use ResolvesDateRange;

    public function __invoke(Request $request, ListTimetableSessionsAction $listSessions): AnonymousResourceCollection
    {
        $user = $request->user();

        if ($user === null || ! $user->can('viewAny', CourseSession::class)) {
            throw new AuthorizationException;
        }

        $scope = TimetableScope::mine($user);
        [$from, $until] = $this->resolveDateRange($request);

        $sessions = $listSessions->execute($scope, $from, $until);

        return CourseSessionResource::collection($sessions);
    }
}
