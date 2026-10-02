<?php

use App\Enums\ConflictType;
use App\Enums\Permission;
use App\Http\Requests\CourseSessions\CheckCourseSessionBatchRequest;
use App\Http\Requests\CourseSessions\StoreCourseSessionBatchRequest;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create();
    $this->program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $this->program->id, 'teacher_id' => $this->teacher->id, 'total_hours' => 30]);
    $this->room = Room::factory()->create(['course_capacity' => 60, 'exam_capacity' => 30]);
    $this->group = StudentGroup::factory()->create(['program_id' => $this->program->id, 'expected_headcount' => 30]);
});

/**
 * Three Saturdays, morning and afternoon: six slots.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function batchPayload(array $overrides = []): array
{
    $slots = [];

    foreach (['2026-10-10', '2026-10-17', '2026-10-24'] as $day) {
        $slots[] = ['starts_at' => "{$day} 08:30", 'ends_at' => "{$day} 12:30"];
        $slots[] = ['starts_at' => "{$day} 13:30", 'ends_at' => "{$day} 17:30"];
    }

    return [
        'module_id' => test()->module->id,
        'room_id' => test()->room->id,
        'student_group_ids' => [test()->group->id],
        'slots' => $slots,
        ...$overrides,
    ];
}

test('a batch creates every slot as a session of the module, teacher, room and groups', function () {
    $second = StudentGroup::factory()->create(['program_id' => $this->program->id, 'expected_headcount' => 20]);

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.batch.store'), batchPayload(['student_group_ids' => [$this->group->id, $second->id]]))
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', '6 sessions scheduled.');

    $sessions = CourseSession::query()->with('studentGroups')->orderBy('starts_at')->get();

    expect($sessions)->toHaveCount(6)
        ->and($sessions->pluck('teacher_id')->unique()->all())->toBe([$this->teacher->id])
        ->and($sessions->pluck('room_id')->unique()->all())->toBe([$this->room->id])
        ->and($sessions->first()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-10 08:30')
        ->and($sessions->last()->ends_at->format('Y-m-d H:i'))->toBe('2026-10-24 17:30')
        ->and($sessions->every(fn (CourseSession $session) => $session->studentGroups->pluck('id')->sort()->values()->all()
            === collect([$this->group->id, $second->id])->sort()->values()->all()))->toBeTrue();
});

test('a single slot works as single-session scheduling', function () {
    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.batch.store'), batchPayload(['slots' => [['starts_at' => '2026-10-10 08:30', 'ends_at' => '2026-10-10 10:30']]]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', '1 session scheduled.');

    expect(CourseSession::count())->toBe(1);
});

test('a hard conflict on one date rolls the whole batch back and names the slot', function () {
    CourseSession::factory()->between('2026-10-17 14:00', '2026-10-17 16:00')->forGroups(StudentGroup::factory()->create())->create(['room_id' => $this->room->id]);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.store'), batchPayload())
        ->assertStatus(422)
        ->assertJsonPath('slot', ['index' => 3, 'starts_at' => '2026-10-17 13:30', 'ends_at' => '2026-10-17 17:30'])
        ->assertJsonPath('has_hard_conflicts', true)
        ->assertJsonPath('hard_conflicts.0.type', ConflictType::Room->value);

    expect(CourseSession::count())->toBe(1);
});

test('inertia callers get the failing slot and its conflicts under slots', function () {
    CourseSession::factory()->between('2026-10-17 14:00', '2026-10-17 16:00')->forGroups(StudentGroup::factory()->create())->create(['teacher_id' => $this->teacher->id]);

    $this->actingAs($this->coordinator)
        ->from(route('timetable.index'))
        ->post(route('course-sessions.batch.store'), batchPayload())
        ->assertRedirect(route('timetable.index'))
        ->assertSessionHasErrors(['slots' => 'Nothing was saved: the slot on 2026-10-17 (13:30–17:30) has a conflict.']);

    expect(CourseSession::count())->toBe(1);
});

test('two overlapping slots of the same batch collide with each other', function () {
    $payload = batchPayload(['slots' => [
        ['starts_at' => '2026-10-10 08:30', 'ends_at' => '2026-10-10 12:30'],
        ['starts_at' => '2026-10-10 11:00', 'ends_at' => '2026-10-10 13:00'],
    ]]);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.check'), $payload)
        ->assertOk()
        ->assertJsonPath('slots.0.overlaps', [1])
        ->assertJsonPath('slots.1.overlaps', [0])
        ->assertJsonPath('slots.0.has_hard_conflicts', false);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.store'), $payload)
        ->assertStatus(422)
        ->assertJsonPath('slot.index', 1);

    expect(CourseSession::count())->toBe(0);
});

test('a soft conflict without an override rolls the batch back with a 409', function () {
    $this->room->update(['course_capacity' => 20, 'exam_capacity' => 10]);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.store'), batchPayload())
        ->assertStatus(409)
        ->assertJsonPath('slot.index', 0)
        ->assertJsonPath('soft_conflicts.0.type', ConflictType::Capacity->value);

    expect(CourseSession::count())->toBe(0);
});

test('one override covers every soft conflict of the batch and is audited per session', function () {
    $this->room->update(['course_capacity' => 20, 'exam_capacity' => 10]);

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.batch.store'), batchPayload([
            'force_override' => true,
            'justification' => 'The amphitheatre is closed for works this month.',
        ]))
        ->assertSessionHasNoErrors();

    $overrides = ConflictOverride::query()->get();

    expect(CourseSession::count())->toBe(6)
        ->and($overrides)->toHaveCount(6)
        ->and($overrides->pluck('schedulable_id')->sort()->values()->all())->toBe(CourseSession::query()->orderBy('id')->pluck('id')->all())
        ->and($overrides->pluck('justification')->unique()->all())->toBe(['The amphitheatre is closed for works this month.'])
        ->and($overrides->pluck('user_id')->unique()->all())->toBe([$this->coordinator->id]);
});

test('an override still cannot pass a hard conflict', function () {
    CourseSession::factory()->between('2026-10-24 09:00', '2026-10-24 10:00')->forGroups(StudentGroup::factory()->create())->create(['room_id' => $this->room->id]);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.store'), batchPayload([
            'force_override' => true,
            'justification' => 'Trying to force a double booking.',
        ]))
        ->assertStatus(422)
        ->assertJsonPath('slot.index', 4);

    expect(CourseSession::count())->toBe(1)
        ->and(ConflictOverride::count())->toBe(0);
});

test('the batch request refuses force_override from a scheduler without the override permission', function () {
    $scheduler = Mockery::mock(User::factory()->coordinator()->create())->makePartial();
    $scheduler->shouldReceive('hasPermission')->andReturnUsing(
        fn (Permission|string $permission) => $permission !== Permission::OverrideSoftConflicts,
    );

    $request = fn (string $class, array $input) => $class::create(route('course-sessions.batch.store'), 'POST', $input)
        ->setUserResolver(fn () => $scheduler);

    expect($request(StoreCourseSessionBatchRequest::class, ['force_override' => true])->authorize())->toBeFalse()
        ->and($request(StoreCourseSessionBatchRequest::class, [])->authorize())->toBeTrue()
        ->and($request(CheckCourseSessionBatchRequest::class, ['force_override' => true])->authorize())->toBeTrue();
});

test('teachers can neither check nor save a batch', function () {
    $this->actingAs($this->teacher)->postJson(route('course-sessions.batch.check'), batchPayload())->assertForbidden();
    $this->actingAs($this->teacher)->postJson(route('course-sessions.batch.store'), batchPayload())->assertForbidden();

    expect(CourseSession::count())->toBe(0);
});

test('invalid batches are rejected before anything is checked', function (Closure $mutate, string $field) {
    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.store'), $mutate(batchPayload()))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(CourseSession::count())->toBe(0);
})->with([
    'no slots' => [fn (array $payload) => [...$payload, 'slots' => []], 'slots'],
    'more than 60 slots' => [fn (array $payload) => [...$payload, 'slots' => array_map(
        fn (int $day) => ['starts_at' => now()->addDays($day)->format('Y-m-d').' 10:00', 'ends_at' => now()->addDays($day)->format('Y-m-d').' 11:00'],
        range(1, 61),
    )], 'slots'],
    'duplicate slots' => [fn (array $payload) => [...$payload, 'slots' => [$payload['slots'][0], $payload['slots'][0]]], 'slots.1.starts_at'],
    'outside the grid' => [fn (array $payload) => [...$payload, 'slots' => [['starts_at' => '2026-10-10 07:00', 'ends_at' => '2026-10-10 09:00']]], 'slots.0.starts_at'],
    'off the quarter hour' => [fn (array $payload) => [...$payload, 'slots' => [['starts_at' => '2026-10-10 08:10', 'ends_at' => '2026-10-10 09:00']]], 'slots.0.starts_at'],
    'across midnight' => [fn (array $payload) => [...$payload, 'slots' => [['starts_at' => '2026-10-10 20:00', 'ends_at' => '2026-10-11 09:00']]], 'slots.0.ends_at'],
    'ending before it starts' => [fn (array $payload) => [...$payload, 'slots' => [['starts_at' => '2026-10-10 10:00', 'ends_at' => '2026-10-10 09:00']]], 'slots.0.ends_at'],
    'group from another program' => [fn (array $payload) => [...$payload, 'student_group_ids' => [StudentGroup::factory()->create()->id]], 'student_group_ids'],
]);

test('the check reports each slot without saving and shows the syllabus impact per group', function () {
    $second = StudentGroup::factory()->create(['program_id' => $this->program->id, 'expected_headcount' => 40]);
    CourseSession::factory()->between('2026-09-05 08:00', '2026-09-05 10:00')->forGroups($this->group, $second)->create(['module_id' => $this->module->id]);
    CourseSession::factory()->between('2026-09-12 08:00', '2026-09-12 09:30')->forGroups($second)->create(['module_id' => $this->module->id]);
    CourseSession::factory()->between('2026-10-17 14:00', '2026-10-17 16:00')->forGroups(StudentGroup::factory()->create())->create(['room_id' => $this->room->id]);

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.batch.check'), batchPayload(['student_group_ids' => [$this->group->id, $second->id]]))
        ->assertOk()
        ->assertJsonCount(6, 'slots')
        ->assertJsonPath('slots.0.index', 0)
        ->assertJsonPath('slots.0.starts_at', '2026-10-10 08:30')
        ->assertJsonPath('slots.0.has_hard_conflicts', false)
        ->assertJsonPath('slots.3.has_hard_conflicts', true)
        ->assertJsonPath('slots.3.hard_conflicts.0.type', ConflictType::Room->value)
        ->assertJsonPath('slots.0.has_soft_conflicts', true)
        ->assertJsonPath('slots.0.soft_conflicts.0.type', ConflictType::Capacity->value)
        ->assertJsonPath('syllabus.total_hours', 30)
        ->assertJsonPath('syllabus.batch_minutes', 6 * 240)
        ->assertJsonPath('syllabus.groups', collect([
            ['id' => $this->group->id, 'name' => $this->group->name, 'planned_minutes' => 120],
            ['id' => $second->id, 'name' => $second->name, 'planned_minutes' => 210],
        ])->sortBy('name')->values()->all());

    expect(CourseSession::count())->toBe(3);
});

test('the timetable tells the page who may schedule and loads the wizard options only on request', function () {
    $this->actingAs($this->coordinator)
        ->get(route('timetable.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canSchedule', true)
            ->missing('schedulingOptions')
            ->reloadOnly('schedulingOptions', fn (Assert $reload) => $reload
                ->where('schedulingOptions.modules.0.id', $this->module->id)
                ->where('schedulingOptions.modules.0.teacher_id', $this->teacher->id)
                ->where('schedulingOptions.groups.0.expected_headcount', 30)
                ->where('schedulingOptions.rooms.0.course_capacity', 60)
                ->where('schedulingOptions.teachers.0.id', $this->teacher->id)));

    $this->actingAs($this->teacher)
        ->get(route('timetable.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canSchedule', false)
            ->reloadOnly('schedulingOptions', fn (Assert $reload) => $reload->where('schedulingOptions', null)));
});
