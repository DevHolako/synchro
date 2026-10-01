<?php

namespace App\Actions\CourseSessions;

use App\Enums\TimetablePerspective;
use App\Models\Campus;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Scheduling\TimetableScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * What a timetable can be browsed by: active groups, rooms and campuses, and every teacher.
 *
 * The currently selected group, room or campus stays listed even when inactive, so a
 * timetable opened from a link still shows what it is about.
 */
class ListTimetableFilterOptionsAction
{
    /**
     * @return array{groups: list<array{id: int, name: string, code: string|null, academic_year: string}>, teachers: list<array{id: int, name: string}>, rooms: list<array{id: int, name: string, code: string|null, building: string}>, campuses: list<array{id: int, name: string, code: string}>}
     */
    public function execute(TimetableScope $scope): array
    {
        return [
            'groups' => array_values($this->activeOrSelected(StudentGroup::query(), $scope, TimetablePerspective::Group)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'academic_year'])
                ->map(fn (StudentGroup $group): array => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                    'academic_year' => $group->academic_year,
                ])
                ->all()),
            'teachers' => array_values(User::teachers()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $teacher): array => ['id' => $teacher->id, 'name' => $teacher->name])
                ->all()),
            'rooms' => array_values($this->activeOrSelected(Room::query(), $scope, TimetablePerspective::Room)
                ->with('building:id,name')
                ->orderBy('name')
                ->get(['id', 'building_id', 'name', 'code'])
                ->map(fn (Room $room): array => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'code' => $room->code,
                    'building' => $room->building->name,
                ])
                ->all()),
            'campuses' => array_values($this->activeOrSelected(Campus::query(), $scope, TimetablePerspective::Campus)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Campus $campus): array => ['id' => $campus->id, 'name' => $campus->name, 'code' => $campus->code])
                ->all()),
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function activeOrSelected(Builder $query, TimetableScope $scope, TimetablePerspective $perspective): Builder
    {
        $selectedId = $scope->perspective === $perspective ? $scope->subjectId : null;

        return $query->where(fn (Builder $query) => $query
            ->where('is_active', true)
            ->when($selectedId !== null, fn (Builder $query) => $query->orWhere('id', $selectedId)));
    }
}
