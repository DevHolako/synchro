<?php

namespace App\Actions\CourseSessions;

use App\Enums\TimetablePerspective;
use App\Models\CourseSession;
use App\Services\Scheduling\TimetableScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

/**
 * The sessions of one timetable within a date range, with everything a calendar card shows.
 *
 * Past sessions and sessions of since-deactivated modules, rooms or groups are included:
 * the timetable is a record of what was scheduled.
 */
class ListTimetableSessionsAction
{
    /**
     * @param  CarbonImmutable  $from  Inclusive.
     * @param  CarbonImmutable  $until  Exclusive.
     * @return Collection<int, CourseSession>
     */
    public function execute(TimetableScope $scope, CarbonImmutable $from, CarbonImmutable $until): Collection
    {
        $subjectId = $scope->subjectId;

        if ($subjectId === null) {
            return new Collection;
        }

        $query = CourseSession::query()
            ->with([
                'module:id,code,name,color_code',
                'teacher:id,name',
                'room:id,building_id,name,code',
                'room.building:id,name',
                'studentGroups:student_groups.id,student_groups.name,student_groups.code',
                'conflictOverrides:id,schedulable_type,schedulable_id,conflict_type,justification',
            ])
            // Sessions never cross midnight, so their start alone places them in the range.
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<', $until)
            ->orderBy('starts_at')
            ->orderBy('id');

        match ($scope->perspective) {
            TimetablePerspective::Group => $query->whereHas(
                'studentGroups',
                fn (Builder $groups) => $groups->where('student_groups.id', $subjectId),
            ),
            TimetablePerspective::Teacher => $query->where('teacher_id', $subjectId),
            TimetablePerspective::Room => $query->where('room_id', $subjectId),
            TimetablePerspective::Campus => $query->whereHas(
                'room.building',
                fn (Builder $buildings) => $buildings->where('campus_id', $subjectId),
            ),
            // TimetableScope resolves "mine" to a group or a teacher before it gets here.
            TimetablePerspective::Mine => throw new LogicException('An unresolved "mine" scope reached the timetable query.'),
        };

        return $query->get();
    }
}
