<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\AssignInvigilatorsAction;
use App\Actions\Exams\ChangeExamStateAction;
use App\Enums\ExamState;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);
    // Exam documents live on the private disk; publishing writes them through the sync queue.
    Storage::fake('local');

    $this->coordinator = User::factory()->coordinator()->create();
    $this->program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $this->program->id]);
    $this->group = StudentGroup::factory()->create(['program_id' => $this->program->id]);
    StudentProfile::factory()->create(['student_group_id' => $this->group->id]);
    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();
});

function examPayload(array $overrides = []): array
{
    return [
        'exam_period_id' => test()->period->id,
        'module_id' => test()->module->id,
        'student_group_ids' => [test()->group->id],
        'starts_at' => '2026-10-12 09:00',
        'ends_at' => '2026-10-12 11:00',
        ...$overrides,
    ];
}

/**
 * A draft exam for the group, seated in a room of its own so only its group can clash.
 */
function examFor(StudentGroup $group, string $startsAt, string $endsAt): Exam
{
    $exam = Exam::factory()->between($startsAt, $endsAt)->forGroups($group)->create([
        'exam_period_id' => test()->period->id,
        'module_id' => Module::factory()->create(['program_id' => test()->program->id])->id,
    ]);

    app(AllocateExamRoomsAction::class)->execute($exam, [Room::factory()->create(['exam_capacity' => 50])->id]);

    return $exam;
}

function courseSessionFor(StudentGroup $group, string $startsAt, string $endsAt): CourseSession
{
    return CourseSession::factory()->between($startsAt, $endsAt)->forGroups($group)->create();
}

test('coordinators draft an exam for several groups of the module program', function () {
    $second = StudentGroup::factory()->create(['program_id' => $this->program->id]);

    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), examPayload(['student_group_ids' => [$this->group->id, $second->id]]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $exam = Exam::sole();

    expect($exam->state)->toBe(ExamState::Draft)
        ->and($exam->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 09:00')
        ->and($exam->studentGroups->pluck('id')->all())->toEqualCanonicalizing([$this->group->id, $second->id]);
});

test('a draft may overlap a course session, and the check reports the clash as a warning', function () {
    $session = courseSessionFor($this->group, '2026-10-12 10:00', '2026-10-12 12:00');

    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), examPayload())
        ->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)
        ->postJson(route('exams.check'), examPayload(['ignore_exam_id' => Exam::sole()->id]))
        ->assertOk()
        ->assertJsonPath('has_hard_conflicts', true)
        ->assertJsonPath('hard_conflicts.0.type', 'group')
        ->assertJsonPath('hard_conflicts.0.booking_type', 'course_session')
        ->assertJsonPath('hard_conflicts.0.booking_id', $session->id);
});

test('scheduling refuses an exam that clashes with a course session of its groups', function () {
    courseSessionFor($this->group, '2026-10-12 10:00', '2026-10-12 12:00');
    $exam = examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');

    $this->actingAs($this->coordinator)
        ->post(route('exams.schedule', $exam))
        ->assertSessionHasErrors('conflicts');

    expect($exam->refresh()->state)->toBe(ExamState::Draft);
});

test('scheduled exams of a group collide with each other, drafts do not', function () {
    $draft = examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');
    $exam = examFor($this->group, '2026-10-12 10:00', '2026-10-12 12:00');

    $this->actingAs($this->coordinator)->post(route('exams.schedule', $exam))->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)
        ->post(route('exams.schedule', $draft))
        ->assertSessionHasErrors('conflicts');

    expect($exam->refresh()->state)->toBe(ExamState::Scheduled)
        ->and($draft->refresh()->state)->toBe(ExamState::Draft);
});

test('a scheduled exam blocks course sessions for its groups, a draft does not', function () {
    $room = Room::factory()->create(['course_capacity' => 200, 'exam_capacity' => 100]);
    $teacher = User::factory()->teacher()->create();
    $sessionPayload = fn (string $start, string $end) => [
        'module_id' => $this->module->id,
        'teacher_id' => $teacher->id,
        'room_id' => $room->id,
        'student_group_ids' => [$this->group->id],
        'starts_at' => $start,
        'ends_at' => $end,
    ];

    examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');
    Exam::factory()->scheduled()->between('2026-10-13 09:00', '2026-10-13 11:00')->forGroups($this->group)
        ->create(['exam_period_id' => $this->period->id]);

    $this->actingAs($this->coordinator)
        ->post(route('course-sessions.store'), $sessionPayload('2026-10-12 10:00', '2026-10-12 12:00'))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)
        ->postJson(route('course-sessions.store'), $sessionPayload('2026-10-13 10:00', '2026-10-13 12:00'))
        ->assertStatus(422)
        ->assertJsonPath('conflicts.0.booking_type', 'exam');
});

test('editing a scheduled exam checks it again, editing a draft does not', function () {
    courseSessionFor($this->group, '2026-10-14 10:00', '2026-10-14 12:00');
    $exam = examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');
    $moved = examPayload(['starts_at' => '2026-10-14 09:00', 'ends_at' => '2026-10-14 11:00']);

    $this->actingAs($this->coordinator)->put(route('exams.update', $exam), $moved)->assertSessionHasNoErrors();
    $this->actingAs($this->coordinator)->put(route('exams.update', $exam), examPayload())->assertSessionHasNoErrors();
    $this->actingAs($this->coordinator)->post(route('exams.schedule', $exam))->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)
        ->put(route('exams.update', $exam), $moved)
        ->assertSessionHasErrors('conflicts');

    expect($exam->refresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 09:00');
});

test('the lifecycle runs draft, scheduled, published and only allows its own moves', function () {
    $exam = examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');

    $this->actingAs($this->coordinator)->post(route('exams.publish', $exam))->assertSessionHasErrors('exam');

    $this->actingAs($this->coordinator)->post(route('exams.schedule', $exam))->assertSessionHasNoErrors();
    $this->actingAs($this->coordinator)->post(route('exams.unschedule', $exam))->assertSessionHasNoErrors();
    expect($exam->refresh()->state)->toBe(ExamState::Draft);

    $this->actingAs($this->coordinator)->post(route('exams.schedule', $exam))->assertSessionHasNoErrors();
    app(AssignInvigilatorsAction::class)->execute($exam->roomAssignments()->sole(), User::factory()->teacher()->create()->id, []);
    $this->actingAs($this->coordinator)->post(route('exams.publish', $exam))->assertSessionHasNoErrors();

    $exam->refresh();

    expect($exam->state)->toBe(ExamState::Published)
        ->and($exam->published_by)->toBe($this->coordinator->id)
        ->and($exam->published_at)->not->toBeNull();

    $this->actingAs($this->coordinator)->post(route('exams.unschedule', $exam))->assertSessionHasErrors('exam');
});

test('a published exam can be neither edited nor deleted', function () {
    $exam = Exam::factory()->published()->forGroups($this->group)
        ->create(['exam_period_id' => $this->period->id, 'module_id' => $this->module->id]);

    $this->actingAs($this->coordinator)
        ->put(route('exams.update', $exam), examPayload(['starts_at' => '2026-10-20 09:00', 'ends_at' => '2026-10-20 11:00']))
        ->assertSessionHasErrors('exam');

    $this->actingAs($this->coordinator)->delete(route('exams.destroy', $exam))->assertSessionHasErrors('exam');

    expect($exam->refresh()->starts_at->format('Y-m-d'))->not->toBe('2026-10-20');
});

test('drafts and scheduled exams can be deleted', function () {
    $draft = examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');
    $scheduled = Exam::factory()->scheduled()->forGroups($this->group)->create(['exam_period_id' => $this->period->id]);

    $this->actingAs($this->coordinator)->delete(route('exams.destroy', $draft))->assertSessionHasNoErrors();
    $this->actingAs($this->coordinator)->delete(route('exams.destroy', $scheduled))->assertSessionHasNoErrors();

    expect(Exam::count())->toBe(0);
});

test('an exam cannot be created in the past, nor scheduled or published once it has started', function () {
    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), examPayload(['starts_at' => '2026-10-02 09:00', 'ends_at' => '2026-10-02 11:00']))
        ->assertSessionHasErrors('starts_at');

    $overdue = Exam::factory()->scheduled()->between('2026-10-02 09:00', '2026-10-02 11:00')->forGroups($this->group)
        ->create(['exam_period_id' => $this->period->id]);

    $this->actingAs($this->coordinator)->post(route('exams.publish', $overdue))->assertSessionHasErrors('exam');

    expect($overdue->refresh()->state)->toBe(ExamState::Scheduled)
        ->and($overdue->isOverdue())->toBeTrue();
});

test('an exam must fall inside its period', function () {
    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), examPayload(['starts_at' => '2026-11-02 09:00', 'ends_at' => '2026-11-02 11:00']))
        ->assertSessionHasErrors('starts_at');

    expect(Exam::count())->toBe(0);
});

test('a group sits a module exam once per period', function () {
    $this->actingAs($this->coordinator)->post(route('exams.store'), examPayload())->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), examPayload(['starts_at' => '2026-10-15 09:00', 'ends_at' => '2026-10-15 11:00']))
        ->assertSessionHasErrors('student_group_ids');

    $retake = ExamPeriod::factory()->retake()->between('2026-10-01', '2026-11-30')->create();

    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), examPayload(['exam_period_id' => $retake->id]))
        ->assertSessionHasNoErrors();

    expect(Exam::count())->toBe(2);
});

test('exam times and groups follow the booking rules', function (array $overrides, string $field) {
    $payload = examPayload($overrides);

    if ($overrides === []) {
        $payload['student_group_ids'] = [StudentGroup::factory()->create()->id];
    }

    $this->actingAs($this->coordinator)
        ->post(route('exams.store'), $payload)
        ->assertSessionHasErrors($field);
})->with([
    'group from another program' => [[], 'student_group_ids'],
    'outside the grid' => [['starts_at' => '2026-10-12 21:00', 'ends_at' => '2026-10-12 23:00'], 'starts_at'],
    'off a quarter hour' => [['starts_at' => '2026-10-12 09:10'], 'starts_at'],
]);

test('only exam managers write exams', function () {
    $exam = examFor($this->group, '2026-10-12 09:00', '2026-10-12 11:00');
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->post(route('exams.store'), examPayload())->assertForbidden();
    $this->actingAs($teacher)->post(route('exams.schedule', $exam))->assertForbidden();
    $this->actingAs($teacher)->delete(route('exams.destroy', $exam))->assertForbidden();
});

test('a move read from a stale state applies only once', function () {
    $exam = Exam::factory()->scheduled()->forGroups($this->group)->create(['exam_period_id' => $this->period->id]);
    $stale = Exam::findOrFail($exam->id);

    app(ChangeExamStateAction::class)->execute($exam, ExamState::Published, ['published_by' => $this->coordinator->id]);

    expect(fn () => app(ChangeExamStateAction::class)->execute($stale, ExamState::Draft))
        ->toThrow(ValidationException::class);

    expect($exam->refresh()->state)->toBe(ExamState::Published);
});

test('published exams that have ended are completed by the scheduler', function () {
    $ended = Exam::factory()->published()->between('2026-10-05 08:00', '2026-10-05 10:00')->create(['exam_period_id' => $this->period->id]);
    $ongoing = Exam::factory()->published()->between('2026-10-05 09:00', '2026-10-05 11:00')->create(['exam_period_id' => $this->period->id]);
    $overdue = Exam::factory()->scheduled()->between('2026-10-05 08:00', '2026-10-05 10:00')->create(['exam_period_id' => $this->period->id]);

    $this->travelTo('2026-10-05 10:00');

    $this->artisan('exams:complete-ended')->assertSuccessful();

    expect($ended->refresh()->state)->toBe(ExamState::Completed)
        ->and($ongoing->refresh()->state)->toBe(ExamState::Published)
        ->and($overdue->refresh()->state)->toBe(ExamState::Scheduled);
});
