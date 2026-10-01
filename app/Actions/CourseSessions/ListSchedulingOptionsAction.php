<?php

namespace App\Actions\CourseSessions;

use App\Models\Module;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;

/**
 * What the scheduling wizard lets a coordinator pick: active modules, groups and rooms, and teachers.
 */
class ListSchedulingOptionsAction
{
    /**
     * @return array{
     *     modules: list<array{id: int, program_id: int, teacher_id: int|null, code: string, name: string, color_code: string, total_hours: int}>,
     *     groups: list<array{id: int, program_id: int, name: string, code: string|null, academic_year: string, expected_headcount: int}>,
     *     rooms: list<array{id: int, name: string, building: string, course_capacity: int}>,
     *     teachers: list<array{id: int, name: string}>
     * }
     */
    public function execute(): array
    {
        return [
            'modules' => array_values(Module::query()
                ->active()
                ->orderBy('code')
                ->get(['id', 'program_id', 'teacher_id', 'code', 'name', 'color_code', 'total_hours'])
                ->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'program_id' => $module->program_id,
                    'teacher_id' => $module->teacher_id,
                    'code' => $module->code,
                    'name' => $module->name,
                    'color_code' => $module->color_code,
                    'total_hours' => $module->total_hours,
                ])
                ->all()),
            'groups' => array_values(StudentGroup::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'program_id', 'name', 'code', 'academic_year', 'expected_headcount'])
                ->map(fn (StudentGroup $group): array => [
                    'id' => $group->id,
                    'program_id' => $group->program_id,
                    'name' => $group->name,
                    'code' => $group->code,
                    'academic_year' => $group->academic_year,
                    'expected_headcount' => $group->expected_headcount,
                ])
                ->all()),
            'rooms' => array_values(Room::query()
                ->active()
                ->with('building:id,name')
                ->get(['id', 'building_id', 'name', 'course_capacity'])
                ->sortBy([['building.name', 'asc'], ['name', 'asc']])
                ->map(fn (Room $room): array => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'building' => $room->building->name,
                    'course_capacity' => $room->course_capacity,
                ])
                ->all()),
            'teachers' => array_values(User::teachers()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $teacher): array => ['id' => $teacher->id, 'name' => $teacher->name])
                ->all()),
        ];
    }
}
