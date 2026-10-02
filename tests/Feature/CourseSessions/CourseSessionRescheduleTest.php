<?php

use App\Enums\ConflictType;
use App\Enums\Permission;
use App\Http\Requests\CourseSessions\RescheduleCourseSessionRequest;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Routing\Route;

beforeEach(function () {
    $this->travelTo('2026-10-07 09:00');
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create();
    $program = Program::factory()->create();
    $this->room = Room::factory()->create(['course_capacity' => 60, 'exam_capacity' => 30]);
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id, 'expected_headcount' => 30]);
    $this->session = CourseSession::factory()->between('2026-10-12 10:00', '2026-10-12 12:00')->create([
        'module_id' => Module::factory()->create(['program_id' => $program->id])->id,
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
    ]);
    $this->session->studentGroups()->attach($this->group->id);
});

function reschedule(array $input, ?User $user = null)
{
    return test()->actingAs($user ?? test()->coordinator)
        ->patchJson(route('course-sessions.reschedule', test()->session), $input);
}

test('a drop moves the session and keeps its module, teacher, room and groups', function () {
    reschedule(['starts_at' => '2026-10-14 14:00', 'ends_at' => '2026-10-14 16:30'])->assertNoContent();

    $session = $this->session->fresh();

    expect($session->starts_at->format('Y-m-d H:i'))->toBe('2026-10-14 14:00')
        ->and($session->ends_at->format('Y-m-d H:i'))->toBe('2026-10-14 16:30')
        ->and($session->room_id)->toBe($this->room->id)
        ->and($session->teacher_id)->toBe($this->teacher->id)
        ->and($session->studentGroups->pluck('id')->all())->toBe([$this->group->id]);
});

test('a hard conflict is a 422 with structured conflicts and useHttp-readable errors, and nothing moves', function () {
    CourseSession::factory()->between('2026-10-13 10:00', '2026-10-13 12:00')->create(['room_id' => $this->room->id]);

    reschedule(['starts_at' => '2026-10-13 11:00', 'ends_at' => '2026-10-13 13:00'])
        ->assertStatus(422)
        ->assertJsonPath('conflicts.0.type', ConflictType::Room->value)
        ->assertJsonStructure(['errors' => ['conflicts']]);

    expect($this->session->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 10:00');
});

test('a soft conflict is a 409 without an override, and saves with an audited override', function () {
    $this->room->update(['course_capacity' => 20, 'exam_capacity' => 10]);
    $times = ['starts_at' => '2026-10-14 14:00', 'ends_at' => '2026-10-14 16:00'];

    reschedule($times)
        ->assertStatus(409)
        ->assertJsonPath('soft_conflicts.0.type', ConflictType::Capacity->value);

    expect($this->session->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 10:00');

    reschedule([...$times, 'force_override' => true, 'justification' => 'Only room free that afternoon.'])->assertNoContent();

    expect($this->session->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-14 14:00')
        ->and(ConflictOverride::query()->sole()->schedulable_id)->toBe($this->session->id);
});

test('a session of a since-deactivated room can still be moved', function () {
    $this->room->update(['is_active' => false]);

    reschedule(['starts_at' => '2026-10-15 08:00', 'ends_at' => '2026-10-15 10:00'])->assertNoContent();
});

test('invalid moves are rejected', function (array $input, string $field) {
    reschedule($input)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'into the past' => [['starts_at' => '2026-10-06 10:00', 'ends_at' => '2026-10-06 12:00'], 'starts_at'],
    'outside the grid' => [['starts_at' => '2026-10-14 21:00', 'ends_at' => '2026-10-14 23:00'], 'starts_at'],
    'off the quarter hour' => [['starts_at' => '2026-10-14 10:05', 'ends_at' => '2026-10-14 12:00'], 'starts_at'],
    'across midnight' => [['starts_at' => '2026-10-14 20:00', 'ends_at' => '2026-10-15 09:00'], 'ends_at'],
    'ending before it starts' => [['starts_at' => '2026-10-14 12:00', 'ends_at' => '2026-10-14 10:00'], 'ends_at'],
]);

test('a session that has started cannot be moved or deleted', function () {
    $this->travelTo('2026-10-12 10:30');

    reschedule(['starts_at' => '2026-10-14 14:00', 'ends_at' => '2026-10-14 16:00'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('starts_at');

    $this->actingAs($this->coordinator)
        ->deleteJson(route('course-sessions.destroy', $this->session))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('session');

    expect(CourseSession::query()->whereKey($this->session->id)->exists())->toBeTrue();
});

test('the lock follows the school clock, not the server clock', function () {
    // 09:30 UTC is 11:30 in Paris (summer time): the 10:00 session has started there.
    config(['app.schedule_timezone' => 'Europe/Paris']);
    $this->travelTo('2026-10-12 09:30');

    reschedule(['starts_at' => '2026-10-14 14:00', 'ends_at' => '2026-10-14 16:00'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('starts_at');

    $this->actingAs($this->coordinator)
        ->deleteJson(route('course-sessions.destroy', $this->session))
        ->assertUnprocessable();
});

test('a future session can be deleted', function () {
    $this->actingAs($this->coordinator)
        ->delete(route('course-sessions.destroy', $this->session))
        ->assertRedirect();

    expect(CourseSession::query()->whereKey($this->session->id)->exists())->toBeFalse();
});

test('teachers cannot move sessions', function () {
    reschedule(['starts_at' => '2026-10-14 14:00', 'ends_at' => '2026-10-14 16:00'], $this->teacher)->assertForbidden();
});

test('overriding needs the override permission', function () {
    $scheduler = Mockery::mock(User::factory()->coordinator()->create())->makePartial();
    $scheduler->shouldReceive('hasPermission')->andReturnUsing(
        fn (Permission|string $permission) => $permission !== Permission::OverrideSoftConflicts,
    );
    $scheduler->shouldReceive('can')->andReturnTrue();

    $request = fn (array $input) => RescheduleCourseSessionRequest::create('/', 'PATCH', $input)
        ->setUserResolver(fn () => $scheduler)
        ->setRouteResolver(fn () => tap(new Route('PATCH', '/', []), fn ($route) => $route->bind(request())->setParameter('session', $this->session)));

    expect($request(['force_override' => true])->authorize())->toBeFalse()
        ->and($request([])->authorize())->toBeTrue();
});
