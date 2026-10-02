<?php

use App\Enums\ConflictType;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Campus;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // A Wednesday: the default week runs Monday 2026-10-05 to Sunday 2026-10-11.
    $this->travelTo('2026-10-07 09:00');

    $this->campus = Campus::factory()->create(['name' => 'Campus Casablanca']);
    $this->room = Room::factory()->create(['building_id' => Building::factory()->create(['campus_id' => $this->campus->id])->id]);
    $this->program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $this->program->id]);
    $this->group = StudentGroup::factory()->create(['program_id' => $this->program->id]);
    $this->teacher = User::factory()->teacher()->create();
    $this->coordinator = User::factory()->coordinator()->create();
});

/**
 * @param  array<string, mixed>  $attributes
 * @param  list<StudentGroup>|null  $groups
 */
function timetableSession(string $startsAt, string $endsAt, array $attributes = [], ?array $groups = null): CourseSession
{
    return CourseSession::factory()->between($startsAt, $endsAt)->forGroups(...$groups ?? [test()->group])->create([
        'module_id' => test()->module->id,
        'teacher_id' => test()->teacher->id,
        'room_id' => test()->room->id,
        ...$attributes,
    ]);
}

/**
 * @return Closure(array<int, array<string, mixed>>): bool
 */
function sessionIds(CourseSession ...$expected): Closure
{
    return fn ($sessions) => collect($sessions)->pluck('id')->all() === collect($expected)->pluck('id')->all();
}

test('only administrators and coordinators may browse every timetable', function (UserRole $role, bool $browses) {
    expect(User::factory()->create(['role' => $role])->hasPermission(Permission::BrowseSchedules))->toBe($browses);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'coordinator' => [UserRole::Coordinator, true],
    'teacher' => [UserRole::Teacher, false],
    'student' => [UserRole::Student, false],
]);

test('guests are sent to the login page', function () {
    $this->get(route('timetable.index'))->assertRedirect(route('login'));
});

test('browsers land on the first active campus by name, for the current week', function () {
    Campus::factory()->inactive()->create(['name' => 'Campus Agadir']);
    Campus::factory()->create(['name' => 'Campus Rabat']);
    $session = timetableSession('2026-10-06 10:00', '2026-10-06 12:00');

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('timetable/index')
            ->where('filters', ['perspective' => 'campus', 'id' => $this->campus->id, 'date' => '2026-10-05', 'view' => null])
            ->where('scope', ['perspective' => 'campus', 'id' => $this->campus->id])
            ->where('canBrowse', true)
            ->where('sessions', sessionIds($session))
            ->where('syllabus', null)
            ->where('noGroup', false)
            ->has('options.campuses', 2));
});

test('each perspective shows only its own sessions', function () {
    $otherGroup = StudentGroup::factory()->create(['program_id' => $this->program->id]);
    $otherTeacher = User::factory()->teacher()->create();
    $otherRoom = Room::factory()->create();

    $base = timetableSession('2026-10-05 08:00', '2026-10-05 10:00');
    $otherGroupSession = timetableSession('2026-10-06 08:00', '2026-10-06 10:00', groups: [$otherGroup]);
    $otherTeacherSession = timetableSession('2026-10-07 08:00', '2026-10-07 10:00', ['teacher_id' => $otherTeacher->id]);
    $otherRoomSession = timetableSession('2026-10-08 08:00', '2026-10-08 10:00', ['room_id' => $otherRoom->id]);

    $expectations = [
        ['group', $this->group->id, [$base, $otherTeacherSession, $otherRoomSession]],
        ['group', $otherGroup->id, [$otherGroupSession]],
        ['teacher', $this->teacher->id, [$base, $otherGroupSession, $otherRoomSession]],
        ['teacher', $otherTeacher->id, [$otherTeacherSession]],
        ['room', $this->room->id, [$base, $otherGroupSession, $otherTeacherSession]],
        ['room', $otherRoom->id, [$otherRoomSession]],
        ['campus', $this->campus->id, [$base, $otherGroupSession, $otherTeacherSession]],
        ['campus', $otherRoom->building->campus_id, [$otherRoomSession]],
    ];

    foreach ($expectations as [$perspective, $id, $expected]) {
        $this->actingAs($this->coordinator)
            ->get(route('timetable.index', ['perspective' => $perspective, 'id' => $id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('scope', ['perspective' => $perspective, 'id' => $id])
                ->where('sessions', sessionIds(...$expected)));
    }
});

test('a perspective without a selection shows no sessions', function () {
    timetableSession('2026-10-05 08:00', '2026-10-05 10:00');

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'group']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scope', ['perspective' => 'group', 'id' => null])
            ->where('sessions', [])
            ->where('syllabus', null));
});

test('the week is half-open: sessions start on or after monday and before the next monday', function () {
    timetableSession('2026-10-04 20:00', '2026-10-04 22:00');
    $monday = timetableSession('2026-10-05 08:00', '2026-10-05 10:00');
    $sunday = timetableSession('2026-10-11 20:00', '2026-10-11 22:00');
    timetableSession('2026-10-12 08:00', '2026-10-12 10:00');

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'group', 'id' => $this->group->id, 'date' => '2026-10-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.date', '2026-10-05')
            ->where('sessions', sessionIds($monday, $sunday)));
});

test('each view loads exactly the dates it displays', function (string $view, string $anchor, array $inside, array $outside) {
    $expected = array_map(fn (string $day) => timetableSession("{$day} 10:00", "{$day} 12:00"), $inside);
    foreach ($outside as $day) {
        timetableSession("{$day} 10:00", "{$day} 12:00");
    }

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'group', 'id' => $this->group->id, 'date' => '2026-10-15', 'view' => $view]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.view', $view)
            ->where('filters.date', $anchor)
            ->where('sessions', sessionIds(...$expected)));
})->with([
    'day grid' => ['timeGridDay', '2026-10-15', ['2026-10-15'], ['2026-10-14', '2026-10-16']],
    'day list' => ['listDay', '2026-10-15', ['2026-10-15'], ['2026-10-16']],
    'week grid' => ['timeGridWeek', '2026-10-12', ['2026-10-12', '2026-10-18'], ['2026-10-11', '2026-10-19']],
    'week list' => ['listWeek', '2026-10-12', ['2026-10-12', '2026-10-18'], ['2026-10-19']],
    // Six weeks from the Monday before the 1st: 2026-09-28 up to 2026-11-09.
    'month' => ['dayGridMonth', '2026-10-01', ['2026-09-28', '2026-11-08'], ['2026-09-27', '2026-11-09']],
]);

test('invalid filters are rejected', function (array $query, string $field) {
    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', $query))
        ->assertSessionHasErrors($field);
})->with([
    'unknown perspective' => [['perspective' => 'building'], 'perspective'],
    'unknown view' => [['view' => 'multiMonthYear'], 'view'],
    'malformed date' => [['date' => '05/10/2026'], 'date'],
    'non-numeric id' => [['perspective' => 'room', 'id' => 'abc'], 'id'],
]);

test('sessions are sent as wall-clock calendar events with their overrides', function () {
    $this->module->update(['code' => 'ALG-101', 'color_code' => '#2563eb']);
    $session = timetableSession('2026-10-06 08:30', '2026-10-06 11:45');
    ConflictOverride::query()->create([
        'user_id' => $this->coordinator->id,
        'schedulable_type' => 'course_session',
        'schedulable_id' => $session->id,
        'conflict_type' => ConflictType::Capacity,
        'justification' => 'The larger room is closed for works.',
        'details' => [],
    ]);

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'room', 'id' => $this->room->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.start', '2026-10-06T08:30:00')
            ->where('sessions.0.end', '2026-10-06T11:45:00')
            ->where('sessions.0.module.code', 'ALG-101')
            ->where('sessions.0.module.color_code', '#2563eb')
            ->where('sessions.0.teacher', ['id' => $this->teacher->id, 'name' => $this->teacher->name])
            ->where('sessions.0.room.building', $this->room->building->name)
            ->where('sessions.0.groups.0.id', $this->group->id)
            ->where('sessions.0.overrides.0.type', 'capacity')
            ->where('sessions.0.overrides.0.justification', 'The larger room is closed for works.'));
});

test('past sessions and sessions of deactivated modules, rooms and groups stay on the timetable', function () {
    $this->travelTo('2026-12-01 09:00');
    $session = timetableSession('2026-10-06 08:00', '2026-10-06 10:00');
    $this->module->update(['is_active' => false]);
    $this->room->update(['is_active' => false]);
    $this->group->update(['is_active' => false]);

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'group', 'id' => $this->group->id, 'date' => '2026-10-06']))
        ->assertInertia(fn (Assert $page) => $page->where('sessions', sessionIds($session)));
});

test('filter options list active items, plus the selected one even when inactive', function () {
    $inactiveGroup = StudentGroup::factory()->inactive()->create(['program_id' => $this->program->id]);
    StudentGroup::factory()->inactive()->create(['program_id' => $this->program->id]);
    Room::factory()->create(['is_active' => false]);

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'group', 'id' => $inactiveGroup->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('options.groups', fn ($groups) => collect($groups)->pluck('id')->sort()->values()->all()
                === collect([$this->group->id, $inactiveGroup->id])->sort()->values()->all())
            ->where('options.rooms', fn ($rooms) => collect($rooms)->pluck('id')->all() === [$this->room->id])
            ->where('options.teachers', fn ($teachers) => collect($teachers)->pluck('id')->all() === [$this->teacher->id]));
});

test('moving to another period reloads only the sessions and filters', function () {
    timetableSession('2026-10-13 08:00', '2026-10-13 10:00');

    $this->actingAs($this->coordinator)
        ->get(route('timetable.index', ['perspective' => 'group', 'id' => $this->group->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sessions', 0)
            ->reloadOnly(['sessions', 'filters'], fn (Assert $reload) => $reload
                ->missing('options')
                ->missing('syllabus')));
});

test('the number of queries does not grow with the number of sessions', function () {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->coordinator)
            ->get(route('timetable.index', ['perspective' => 'campus', 'id' => $this->campus->id]))
            ->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $seed = function (int $count): void {
        foreach (range(1, $count) as $index) {
            $room = Room::factory()->create(['building_id' => $this->room->building_id]);
            $session = timetableSession('2026-10-06 08:00', '2026-10-06 10:00', [
                'room_id' => $room->id,
                'teacher_id' => User::factory()->teacher()->create()->id,
                'module_id' => Module::factory()->create(['program_id' => $this->program->id])->id,
            ], [StudentGroup::factory()->create(['program_id' => $this->program->id])]);
            ConflictOverride::query()->create([
                'user_id' => $this->coordinator->id,
                'schedulable_type' => 'course_session',
                'schedulable_id' => $session->id,
                'conflict_type' => ConflictType::Capacity,
                'justification' => fake()->sentence(),
                'details' => [],
            ]);
        }
    };

    $seed(3);
    $few = $countQueries();
    $seed(27);
    $many = $countQueries();

    expect($many)->toBe($few);
});

test('a student sees their own group timetable and its syllabus progress', function () {
    $profile = StudentProfile::factory()->create(['student_group_id' => $this->group->id]);
    $session = timetableSession('2026-10-06 08:00', '2026-10-06 10:00');
    timetableSession('2026-10-07 08:00', '2026-10-07 10:00', groups: [StudentGroup::factory()->create(['program_id' => $this->program->id])]);

    $this->actingAs($profile->user)
        ->get(route('timetable.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.perspective', 'mine')
            ->where('filters.id', null)
            ->where('scope', ['perspective' => 'group', 'id' => $this->group->id])
            ->where('canBrowse', false)
            ->where('options', null)
            ->where('noGroup', false)
            ->where('sessions', sessionIds($session))
            ->has('syllabus', 1));
});

test('a student without a group gets an empty timetable, not an error', function () {
    $profile = StudentProfile::factory()->create(['student_group_id' => null]);
    timetableSession('2026-10-06 08:00', '2026-10-06 10:00');

    $this->actingAs($profile->user)
        ->get(route('timetable.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('noGroup', true)
            ->where('sessions', [])
            ->where('syllabus', null));
});

test('a teacher sees the sessions they teach', function () {
    $session = timetableSession('2026-10-06 08:00', '2026-10-06 10:00');
    timetableSession('2026-10-07 08:00', '2026-10-07 10:00', ['teacher_id' => User::factory()->teacher()->create()->id]);

    $this->actingAs($this->teacher)
        ->get(route('timetable.index', ['perspective' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('scope', ['perspective' => 'teacher', 'id' => $this->teacher->id])
            ->where('options', null)
            ->where('syllabus', null)
            ->where('sessions', sessionIds($session)));
});

test('users without browsing rights cannot open another timetable', function (string $perspective) {
    $profile = StudentProfile::factory()->create(['student_group_id' => $this->group->id]);

    $this->actingAs($profile->user)
        ->get(route('timetable.index', ['perspective' => $perspective, 'id' => 1]))
        ->assertForbidden();

    $this->actingAs($this->teacher)
        ->get(route('timetable.index', ['perspective' => $perspective, 'id' => 1]))
        ->assertForbidden();
})->with(['campus', 'group', 'teacher', 'room']);
