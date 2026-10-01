<?php

use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create(['name' => 'Pr. Alaoui']);
    $this->program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $this->program->id, 'teacher_id' => $this->teacher->id]);
    $this->room = Room::factory()->create(['name' => 'Amphi A']);
    $this->group = StudentGroup::factory()->create(['program_id' => $this->program->id, 'name' => '1CI-A']);
});

function sessionPayload(array $overrides = []): array
{
    return [
        'module_id' => test()->module->id,
        'room_id' => test()->room->id,
        'student_group_ids' => [test()->group->id],
        'starts_at' => '2026-10-12 10:00',
        'ends_at' => '2026-10-12 12:00',
        ...$overrides,
    ];
}

test('coordinators schedule a session for several groups, defaulting to the module teacher', function () {
    $second = StudentGroup::factory()->create(['program_id' => $this->program->id]);

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), sessionPayload(['student_group_ids' => [$this->group->id, $second->id]]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $session = CourseSession::sole();

    expect($session->teacher_id)->toBe($this->teacher->id)
        ->and($session->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 10:00')
        ->and($session->studentGroups->pluck('id')->all())->toEqualCanonicalizing([$this->group->id, $second->id]);
});

test('a substitute teacher can be given instead of the module teacher', function () {
    $substitute = User::factory()->teacher()->create();

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), sessionPayload(['teacher_id' => $substitute->id]))
        ->assertSessionHasNoErrors();

    expect(CourseSession::sole()->teacher_id)->toBe($substitute->id);
});

test('a hard conflict is rejected with translated messages for inertia callers', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload());

    $this->actingAs($this->coordinator)
        ->from('/dashboard')
        ->post(route('course-sessions.store'), sessionPayload(['starts_at' => '2026-10-12 11:00', 'ends_at' => '2026-10-12 13:00']))
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('conflicts');

    expect(CourseSession::count())->toBe(1);
});

test('a hard conflict is rejected with a structured 422 for json callers', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload());
    $existing = CourseSession::sole();

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.store'), sessionPayload(['starts_at' => '2026-10-12 11:00', 'ends_at' => '2026-10-12 13:00']))
        ->assertStatus(422)
        ->assertJsonCount(3, 'conflicts')
        ->assertJsonFragment([
            'type' => 'room',
            'resource_id' => $this->room->id,
            'resource_name' => 'Amphi A',
            'session_id' => $existing->id,
            'starts_at' => '2026-10-12 10:00',
            'ends_at' => '2026-10-12 12:00',
        ]);
});

test('adjacent sessions in the same room are accepted', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload());

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), sessionPayload(['starts_at' => '2026-10-12 12:00', 'ends_at' => '2026-10-12 14:00']))
        ->assertSessionHasNoErrors();

    expect(CourseSession::count())->toBe(2);
});

test('moving a session checks conflicts against other sessions only', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload());
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload(['starts_at' => '2026-10-12 14:00', 'ends_at' => '2026-10-12 16:00']));
    [$morning, $afternoon] = CourseSession::orderBy('starts_at')->get()->all();

    $this->actingAs($this->coordinator)
        ->put(route('course-sessions.update', $morning), sessionPayload(['starts_at' => '2026-10-12 10:30', 'ends_at' => '2026-10-12 12:30']))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)
        ->put(route('course-sessions.update', $morning), sessionPayload(['starts_at' => '2026-10-12 13:00', 'ends_at' => '2026-10-12 15:00']))
        ->assertSessionHasErrors('conflicts');

    expect($morning->refresh()->starts_at->format('H:i'))->toBe('10:30')
        ->and($afternoon->refresh()->starts_at->format('H:i'))->toBe('14:00');
});

test('the check endpoint reports conflicts without saving', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload());
    $existing = CourseSession::sole();

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.check'), sessionPayload(['starts_at' => '2026-10-12 11:00', 'ends_at' => '2026-10-12 12:00']))
        ->assertOk()
        ->assertJsonPath('has_hard_conflicts', true)
        ->assertJsonCount(3, 'hard_conflicts')
        ->assertJsonPath('soft_conflicts', []);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.check'), sessionPayload(['ignore_session_id' => $existing->id]))
        ->assertOk()
        ->assertJsonPath('has_hard_conflicts', false);

    expect(CourseSession::count())->toBe(1);
});

test('coordinators can remove a session', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), sessionPayload());

    $this->actingAs($this->coordinator)->delete(route('course-sessions.destroy', CourseSession::sole()))->assertRedirect();

    expect(CourseSession::count())->toBe(0);
});

test('sessions are validated', function (Closure $payload, string $field) {
    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), $payload())
        ->assertSessionHasErrors($field);

    expect(CourseSession::count())->toBe(0);
})->with([
    'no groups' => [fn () => sessionPayload(['student_group_ids' => []]), 'student_group_ids'],
    'inactive room' => [fn () => sessionPayload(['room_id' => Room::factory()->create(['is_active' => false])->id]), 'room_id'],
    'inactive module' => [fn () => sessionPayload(['module_id' => Module::factory()->create(['program_id' => test()->program->id, 'teacher_id' => test()->teacher->id, 'is_active' => false])->id]), 'module_id'],
    'inactive group' => [fn () => sessionPayload(['student_group_ids' => [StudentGroup::factory()->create(['program_id' => test()->program->id, 'is_active' => false])->id]]), 'student_group_ids.0'],
    'teacher who is not a teacher' => [fn () => sessionPayload(['teacher_id' => User::factory()->student()->create()->id]), 'teacher_id'],
    'module without a teacher and none given' => [fn () => sessionPayload(['module_id' => Module::factory()->create(['program_id' => test()->program->id, 'teacher_id' => null])->id]), 'teacher_id'],
    'group from another program' => [fn () => sessionPayload(['student_group_ids' => [StudentGroup::factory()->create()->id]]), 'student_group_ids'],
    'end before start' => [fn () => sessionPayload(['ends_at' => '2026-10-12 09:00']), 'ends_at'],
    'across midnight' => [fn () => sessionPayload(['starts_at' => '2026-10-12 20:00', 'ends_at' => '2026-10-13 09:00']), 'ends_at'],
    'before the grid' => [fn () => sessionPayload(['starts_at' => '2026-10-12 07:45']), 'starts_at'],
    'after the grid' => [fn () => sessionPayload(['starts_at' => '2026-10-12 21:00', 'ends_at' => '2026-10-12 22:15']), 'starts_at'],
    'off the quarter hour' => [fn () => sessionPayload(['starts_at' => '2026-10-12 10:10']), 'starts_at'],
    'bad datetime format' => [fn () => sessionPayload(['starts_at' => '2026-10-12T10:00:00Z']), 'starts_at'],
]);

test('past sessions can be recorded', function () {
    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), sessionPayload(['starts_at' => '2020-01-06 10:00', 'ends_at' => '2020-01-06 12:00']))
        ->assertSessionHasNoErrors();
});

test('only users holding the manage schedules permission can write or check sessions', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $session = CourseSession::factory()->create();

    $this->actingAs($user)->post(route('course-sessions.store'), sessionPayload())->assertForbidden();
    $this->actingAs($user)->postJson(route('course-sessions.check'), sessionPayload())->assertForbidden();
    $this->actingAs($user)->put(route('course-sessions.update', $session), sessionPayload())->assertForbidden();
    $this->actingAs($user)->delete(route('course-sessions.destroy', $session))->assertForbidden();

    expect(CourseSession::count())->toBe(1);
})->with(['teacher', 'student']);

test('teachers, rooms and modules with sessions cannot be deleted, but groups only unlink', function () {
    $session = CourseSession::factory()->create();
    $session->studentGroups()->attach($this->group);

    expect(fn () => $session->room->delete())->toThrow(QueryException::class)
        ->and(fn () => $session->teacher->delete())->toThrow(QueryException::class)
        ->and(fn () => $session->module->delete())->toThrow(QueryException::class);

    $this->group->delete();

    expect($session->refresh()->studentGroups)->toHaveCount(0);
});
