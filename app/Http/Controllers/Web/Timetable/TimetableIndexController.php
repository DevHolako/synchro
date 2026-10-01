<?php

namespace App\Http\Controllers\Web\Timetable;

use App\Actions\CourseSessions\CalculateSyllabusProgressAction;
use App\Actions\CourseSessions\ListSchedulingOptionsAction;
use App\Actions\CourseSessions\ListTimetableFilterOptionsAction;
use App\Actions\CourseSessions\ListTimetableSessionsAction;
use App\Enums\TimetablePerspective;
use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\TimetableRequest;
use App\Http\Resources\TimetableSessionResource;
use App\Models\CourseSession;
use App\Models\StudentGroup;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The timetable calendar: the viewer's own, or any group's, teacher's, room's or campus's (Part 03 / Ticket 01).
 */
class TimetableIndexController extends Controller
{
    public function __invoke(
        TimetableRequest $request,
        ListTimetableSessionsAction $listSessions,
        CalculateSyllabusProgressAction $calculateSyllabusProgress,
        ListTimetableFilterOptionsAction $listFilterOptions,
        ListSchedulingOptionsAction $listSchedulingOptions,
    ): Response {
        $perspective = $request->perspective();
        $scope = $request->scope();
        [$from, $until] = $request->range();
        $canBrowse = $request->user()?->can('browse', CourseSession::class) ?? false;
        $canSchedule = $request->user()?->can('create', CourseSession::class) ?? false;
        $groupId = $scope->perspective === TimetablePerspective::Group ? $scope->subjectId : null;

        return Inertia::render('timetable/index', [
            'sessions' => fn () => TimetableSessionResource::collection($listSessions->execute($scope, $from, $until))->resolve(),
            'filters' => [
                'perspective' => $perspective->value,
                'id' => $perspective === TimetablePerspective::Mine ? null : $scope->subjectId,
                'date' => $request->anchorDate()->toDateString(),
                'view' => $request->calendarView()?->value,
            ],
            'scope' => ['perspective' => $scope->perspective->value, 'id' => $scope->subjectId],
            'canBrowse' => $canBrowse,
            'options' => fn () => $canBrowse ? $listFilterOptions->execute($scope) : null,
            'canSchedule' => $canSchedule,
            // Loaded by the scheduling wizard the first time it opens.
            'schedulingOptions' => Inertia::optional(fn () => $canSchedule ? $listSchedulingOptions->execute() : null),
            'syllabus' => function () use ($groupId, $calculateSyllabusProgress): ?array {
                $group = $groupId === null ? null : StudentGroup::query()->find($groupId);

                return $group === null ? null : $calculateSyllabusProgress->execute($group);
            },
            // A student whose profile has no group yet.
            'noGroup' => $perspective === TimetablePerspective::Mine
                && $scope->perspective === TimetablePerspective::Group
                && $groupId === null,
        ]);
    }
}
