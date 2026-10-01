<?php

use App\Actions\CourseSessions\CreateCourseSessionAction;
use App\Enums\ConflictType;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Requests\CourseSessions\StoreCourseSessionRequest;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\TeacherUnavailability;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\SessionSlot;
use App\Services\Scheduling\SoftConflictOverride;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create(['name' => 'Pr. Alaoui']);
    $this->program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $this->program->id, 'teacher_id' => $this->teacher->id]);
    $this->room = Room::factory()->create(['name' => 'Salle 12', 'course_capacity' => 60, 'exam_capacity' => 30]);
    $this->groupA = StudentGroup::factory()->create(['program_id' => $this->program->id, 'expected_headcount' => 35]);
    $this->groupB = StudentGroup::factory()->create(['program_id' => $this->program->id, 'expected_headcount' => 25]);
    $this->detector = app(ConflictDetectorService::class);
});

// 2026-10-16 is a Friday.
function softSlot(array $groupIds, string $start = '2026-10-16 18:00', string $end = '2026-10-16 20:00'): SessionSlot
{
    return new SessionSlot(
        teacherId: test()->teacher->id,
        roomId: test()->room->id,
        groupIds: $groupIds,
        startsAt: CarbonImmutable::parse($start),
        endsAt: CarbonImmutable::parse($end),
    );
}

function softPayload(array $overrides = []): array
{
    return [
        'module_id' => test()->module->id,
        'room_id' => test()->room->id,
        'student_group_ids' => [test()->groupA->id, test()->groupB->id, StudentGroup::factory()->create(['program_id' => test()->program->id, 'expected_headcount' => 1])->id],
        'starts_at' => '2026-10-12 10:00',
        'ends_at' => '2026-10-12 12:00',
        ...$overrides,
    ];
}

test('the combined headcount may fill the room exactly but not exceed it', function () {
    expect($this->detector->checkConflicts(softSlot([$this->groupA->id, $this->groupB->id]))->hasSoftConflicts())->toBeFalse();

    $this->groupB->update(['expected_headcount' => 26]);
    $result = $this->detector->checkConflicts(softSlot([$this->groupA->id, $this->groupB->id]));

    expect($result->softConflicts)->toHaveCount(1)
        ->and($result->softConflicts[0]->type)->toBe(ConflictType::Capacity)
        ->and($result->softConflicts[0]->details)->toBe(['capacity' => 60, 'headcount' => 61]);
});

test('a recurring unavailability conflicts on its weekday, within its period and times', function (string $start, string $end, bool $conflicts) {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '18:00', '22:00')
        ->create(['start_date' => '2026-10-01', 'end_date' => '2026-12-31']);

    $result = $this->detector->checkConflicts(softSlot([$this->groupA->id], $start, $end));

    expect($result->hasSoftConflicts())->toBe($conflicts);
})->with([
    'overlapping friday evening' => ['2026-10-16 17:00', '2026-10-16 18:15', true],
    'touching edge' => ['2026-10-16 16:00', '2026-10-16 18:00', false],
    'another weekday' => ['2026-10-15 18:00', '2026-10-15 20:00', false],
    'friday after the period' => ['2027-01-08 18:00', '2027-01-08 20:00', false],
    'friday before the period' => ['2026-09-25 18:00', '2026-09-25 20:00', false],
]);

test('an ad-hoc unavailability conflicts on its dates, whole days or within its window', function (?string $from, ?string $to, string $start, string $end, bool $conflicts) {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc('2026-10-15', '2026-10-17', $from, $to)->create();

    expect($this->detector->checkConflicts(softSlot([$this->groupA->id], $start, $end))->hasSoftConflicts())->toBe($conflicts);
})->with([
    'whole days' => [null, null, '2026-10-16 08:00', '2026-10-16 10:00', true],
    'whole days, day after' => [null, null, '2026-10-18 08:00', '2026-10-18 10:00', false],
    'inside the window' => ['14:00', '16:00', '2026-10-17 15:00', '2026-10-17 17:00', true],
    'outside the window' => ['14:00', '16:00', '2026-10-17 16:00', '2026-10-17 18:00', false],
]);

test('only the teacher own pending or approved unavailabilities count', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc('2026-10-16', '2026-10-16')->rejected()->create();
    TeacherUnavailability::factory()->adHoc('2026-10-16', '2026-10-16')->approved()->create();

    expect($this->detector->checkConflicts(softSlot([$this->groupA->id]))->hasSoftConflicts())->toBeFalse();

    $approved = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc('2026-10-16', '2026-10-16')->approved()->create(['reason' => 'Jury']);
    $result = $this->detector->checkConflicts(softSlot([$this->groupA->id]));

    expect($result->softConflicts)->toHaveCount(1)
        ->and($result->softConflicts[0]->type)->toBe(ConflictType::Unavailability)
        ->and($result->softConflicts[0]->resourceName)->toBe('Pr. Alaoui')
        ->and($result->softConflicts[0]->details)->toMatchArray(['unavailability_id' => $approved->id, 'status' => 'approved', 'reason' => 'Jury']);
});

test('soft conflicts without an override are refused with 409 for json callers', function () {
    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.store'), softPayload())
        ->assertStatus(409)
        ->assertJsonPath('soft_conflicts.0.type', 'capacity')
        ->assertJsonPath('soft_conflicts.0.details.headcount', 61);

    expect(CourseSession::count())->toBe(0);
});

test('soft conflicts without an override come back as errors for inertia callers', function () {
    $this->actingAs($this->coordinator)
        ->from('/dashboard')
        ->post(route('course-sessions.store'), softPayload())
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('soft_conflicts');

    expect(CourseSession::count())->toBe(0);
});

test('hard conflicts win over soft ones and list both', function () {
    CourseSession::factory()->between('2026-10-12 10:00', '2026-10-12 12:00')->create(['room_id' => $this->room->id]);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => 'Exam week, no other room.']))
        ->assertStatus(422)
        ->assertJsonPath('conflicts.0.type', 'room')
        ->assertJsonPath('soft_conflicts.0.type', 'capacity');

    expect(CourseSession::count())->toBe(1)
        ->and(ConflictOverride::count())->toBe(0);
});

test('an override saves the session and audits every soft conflict', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(1, '09:00', '11:00')->approved()->create(['start_date' => '2026-10-01']);

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => '  Agreed verbally with the teacher.  ']))
        ->assertSessionHasNoErrors();

    $session = CourseSession::sole();
    $overrides = ConflictOverride::orderBy('id')->get();

    expect($overrides)->toHaveCount(2)
        ->and($overrides->pluck('conflict_type')->all())->toEqualCanonicalizing([ConflictType::Capacity, ConflictType::Unavailability])
        ->and($overrides->every(fn (ConflictOverride $override) => $override->user_id === $this->coordinator->id
            && $override->schedulable_type === 'course_session'
            && $override->schedulable_id === $session->id
            && $override->justification === 'Agreed verbally with the teacher.'))->toBeTrue()
        ->and($overrides->firstWhere('conflict_type', ConflictType::Capacity)->details)
        ->toMatchArray(['resource_name' => 'Salle 12', 'capacity' => 60, 'headcount' => 61, 'starts_at' => '2026-10-12 10:00'])
        ->and($overrides->first()->schedulable->is($session))->toBeTrue();
});

test('an override needs a justification of at least 10 characters', function (array $extra) {
    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), softPayload(['force_override' => true, ...$extra]))
        ->assertSessionHasErrors('justification');

    expect(CourseSession::count())->toBe(0);
})->with([
    'missing' => [[]],
    'too short' => [['justification' => 'ok']],
    'blank' => [['justification' => '            ']],
]);

test('only the coordinator bundle may override soft conflicts', function () {
    expect(UserRole::Coordinator->hasPermission(Permission::OverrideSoftConflicts))->toBeTrue()
        ->and(UserRole::Administrator->hasPermission(Permission::OverrideSoftConflicts))->toBeTrue()
        ->and(UserRole::Teacher->hasPermission(Permission::OverrideSoftConflicts))->toBeFalse();
});

test('the guard refuses an override from a user without the override permission', function () {
    $teacher = User::factory()->teacher()->create();

    expect(fn () => app(CreateCourseSessionAction::class)->execute(
        [
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'student_group_ids' => [$this->groupA->id],
            'starts_at' => '2026-10-12 10:00',
            'ends_at' => '2026-10-12 12:00',
        ],
        new SoftConflictOverride($teacher, 'Trying to bypass the rules.'),
    ))->toThrow(AuthorizationException::class);

    expect(CourseSession::count())->toBe(0);
});

test('editing a session with a standing soft conflict needs a fresh override', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => 'Exam week, no other room.']));
    $session = CourseSession::sole();

    $this->actingAs($this->coordinator)
        ->putJson(route('course-sessions.update', $session), softPayload(['starts_at' => '2026-10-12 14:00', 'ends_at' => '2026-10-12 16:00']))
        ->assertStatus(409);

    $this->actingAs($this->coordinator)
        ->put(route('course-sessions.update', $session), softPayload(['starts_at' => '2026-10-12 14:00', 'ends_at' => '2026-10-12 16:00', 'force_override' => true, 'justification' => 'Still no bigger room free.']))
        ->assertSessionHasNoErrors();

    expect($session->refresh()->starts_at->format('H:i'))->toBe('14:00')
        ->and(ConflictOverride::count())->toBe(2);
});

test('audit records are immutable and outlive their session', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => 'Exam week, no other room.']));
    $override = ConflictOverride::sole();

    expect(fn () => $override->update(['justification' => 'Rewritten history.']))->toThrow(LogicException::class)
        ->and(fn () => $override->delete())->toThrow(LogicException::class);

    $this->actingAs($this->coordinator)->delete(route('course-sessions.destroy', CourseSession::sole()));

    expect(ConflictOverride::sole()->justification)->toBe('Exam week, no other room.');
});

test('the check endpoint reports soft conflicts too', function () {
    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.check'), softPayload())
        ->assertOk()
        ->assertJsonPath('has_hard_conflicts', false)
        ->assertJsonPath('has_soft_conflicts', true)
        ->assertJsonPath('soft_conflicts.0.type', 'capacity');
});

test('unavailability dates are inclusive whatever type they were assigned with', function () {
    // Carbon dates (as the factory and future writers use) and strings must store alike.
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '18:00', '22:00')
        ->create(['start_date' => CarbonImmutable::parse('2026-10-16'), 'end_date' => CarbonImmutable::parse('2026-10-30')]);
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc('2026-11-02', '2026-11-04')->create();

    $onFirstDay = $this->detector->checkConflicts(softSlot([$this->groupA->id], '2026-10-16 18:00', '2026-10-16 19:00'));
    $onLastDay = $this->detector->checkConflicts(softSlot([$this->groupA->id], '2026-10-30 18:00', '2026-10-30 19:00'));
    $onAdHocLastDay = $this->detector->checkConflicts(softSlot([$this->groupA->id], '2026-11-04 08:00', '2026-11-04 09:00'));

    expect($onFirstDay->hasSoftConflicts())->toBeTrue()
        ->and($onLastDay->hasSoftConflicts())->toBeTrue()
        ->and($onAdHocLastDay->hasSoftConflicts())->toBeTrue();
});

test('a justification sent without force_override is ignored', function () {
    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), softPayload([
            'student_group_ids' => [$this->groupA->id],
            'justification' => 'short',
        ]))
        ->assertSessionHasNoErrors();

    expect(CourseSession::count())->toBe(1)
        ->and(ConflictOverride::count())->toBe(0);
});

test('the request refuses force_override from a scheduler without the override permission', function () {
    $scheduler = Mockery::mock(User::factory()->coordinator()->create())->makePartial();
    $scheduler->shouldReceive('hasPermission')->andReturnUsing(
        fn (Permission|string $permission) => $permission !== Permission::OverrideSoftConflicts,
    );

    $request = fn (array $input) => StoreCourseSessionRequest::create(route('course-sessions.store'), 'POST', $input)
        ->setUserResolver(fn () => $scheduler);

    expect($request(['force_override' => true])->authorize())->toBeFalse()
        ->and($request(['force_override' => false])->authorize())->toBeTrue()
        ->and($request([])->authorize())->toBeTrue();
});

test('inertia requests get conflicts in the errors bag, not json', function () {
    CourseSession::factory()->between('2026-10-12 10:00', '2026-10-12 12:00')->create(['room_id' => $this->room->id]);
    $inertia = ['X-Inertia' => 'true', 'Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

    $this->actingAs($this->coordinator)
        ->from('/dashboard')
        ->withHeaders($inertia)
        ->post(route('course-sessions.store'), softPayload())
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors(['conflicts', 'soft_conflicts']);

    $this->actingAs($this->coordinator)
        ->from('/dashboard')
        ->withHeaders($inertia)
        ->post(route('course-sessions.store'), softPayload(['starts_at' => '2026-10-12 14:00', 'ends_at' => '2026-10-12 16:00']))
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('soft_conflicts')
        ->assertSessionDoesntHaveErrors('conflicts');
});

test('audit records cannot be changed by bulk queries and keep their author', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => 'Exam week, no other room.']));

    expect(fn () => ConflictOverride::query()->update(['justification' => 'Rewritten.']))->toThrow(LogicException::class)
        ->and(fn () => ConflictOverride::query()->delete())->toThrow(LogicException::class)
        ->and(fn () => $this->coordinator->delete())->toThrow(QueryException::class);

    expect(ConflictOverride::sole()->user_id)->toBe($this->coordinator->id);
});

test('accounts anchoring the timetable or its audit cannot delete themselves', function (string $who) {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => 'Exam week, no other room.']));
    $user = $who === 'teacher' ? $this->teacher : $this->coordinator;

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('account');

    expect($user->fresh())->not->toBeNull();
})->with(['teacher', 'coordinator']);

test('audit records refuse increments and upserts as well', function () {
    $this->actingAs($this->coordinator)->post(route('course-sessions.store'), softPayload(['force_override' => true, 'justification' => 'Exam week, no other room.']));
    $override = ConflictOverride::sole();

    expect(fn () => ConflictOverride::query()->increment('schedulable_id'))->toThrow(LogicException::class)
        ->and(fn () => ConflictOverride::query()->upsert([[...$override->getAttributes(), 'justification' => 'Rewritten.']], ['id']))->toThrow(LogicException::class);

    expect($override->fresh()->justification)->toBe('Exam week, no other room.');
});
