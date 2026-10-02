<?php

use App\Actions\CalendarFeeds\IssueCalendarFeedTokenAction;
use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\AssignInvigilatorsAction;
use App\Enums\ConflictType;
use App\Enums\ExamState;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Scheduling\SoftConflictOverride;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);
    // Exam documents live on the private disk; publishing writes them through the sync queue.
    Storage::fake('local');

    $this->coordinator = User::factory()->coordinator()->create();
    $program = Program::factory()->create();
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();
    $this->exam = Exam::factory()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($this->group)->create([
        'exam_period_id' => $this->period->id,
        'module_id' => Module::factory()->create(['program_id' => $program->id])->id,
    ]);
    $this->roomA = Room::factory()->create(['name' => 'Amphi A', 'exam_capacity' => 4]);
    $this->roomB = Room::factory()->create(['name' => 'Salle B', 'exam_capacity' => 4]);
});

function enrol(string ...$surnames): void
{
    foreach ($surnames as $surname) {
        StudentProfile::factory()->create(['student_group_id' => test()->group->id, 'last_name' => $surname]);
    }
}

function allocate(array $roomIds, array $extra = []): TestResponse
{
    return test()->actingAs(test()->coordinator)
        ->putJson(route('exams.rooms.update', test()->exam), ['room_ids' => $roomIds, ...$extra]);
}

test('coordinators pick rooms in order and the candidates are seated alphabetically across them', function () {
    enrol('Tazi', 'Alami', 'Mansouri', 'Benali', 'Zerouali', 'Chraibi');

    allocate([$this->roomB->id, $this->roomA->id])
        ->assertOk()
        ->assertJsonPath('assignments.0.room', 'Salle B')
        ->assertJsonPath('assignments.0.allocated_students_count', 3)
        ->assertJsonPath('assignments.0.first_surname', 'Alami')
        ->assertJsonPath('assignments.0.last_surname', 'Chraibi')
        ->assertJsonPath('assignments.1.first_surname', 'Mansouri')
        ->assertJsonPath('assignments.1.last_surname', 'Zerouali')
        ->assertJsonPath('students_count', 6);

    $seats = ExamCandidate::query()->with(['student.studentProfile', 'roomAssignment'])->get()
        ->mapWithKeys(fn (ExamCandidate $candidate) => [$candidate->student->studentProfile->last_name => [$candidate->roomAssignment->room_id, $candidate->seat_number]]);

    expect($seats['Alami'])->toBe([$this->roomB->id, 1])
        ->and($seats['Chraibi'])->toBe([$this->roomB->id, 3])
        ->and($seats['Mansouri'])->toBe([$this->roomA->id, 1]);
});

test('rooms that seat fewer than the candidates are refused', function () {
    enrol('Alami', 'Benali', 'Chraibi', 'Dahbi', 'Fassi');

    allocate([$this->roomA->id])->assertUnprocessable()->assertJsonValidationErrors('room_ids');

    expect($this->exam->roomAssignments()->count())->toBe(0);
});

test('forcing a single room seats everyone in it, with a justification kept in the audit', function () {
    enrol('Alami', 'Benali', 'Chraibi', 'Dahbi', 'Fassi');

    allocate([$this->roomA->id], ['force_single_room' => true])->assertJsonValidationErrors('justification');
    allocate([$this->roomA->id, $this->roomB->id], ['force_single_room' => true, 'justification' => 'Amphithéâtre surveillé par caméra.'])
        ->assertJsonValidationErrors('room_ids');

    allocate([$this->roomA->id], ['force_single_room' => true, 'justification' => 'Amphithéâtre surveillé par caméra.'])
        ->assertOk()
        ->assertJsonPath('force_single_room', true)
        ->assertJsonPath('assignments.0.allocated_students_count', 5);

    $override = ConflictOverride::sole();

    expect($override->conflict_type)->toBe(ConflictType::ForcedSingleRoom)
        ->and($override->schedulable_type)->toBe('exam')
        ->and($override->details)->toMatchArray(['capacity' => 4, 'headcount' => 5]);
});

test('only holders of the override permission may force a single room', function () {
    $override = new SoftConflictOverride(User::factory()->teacher()->create(), 'Une justification suffisante.');

    expect(fn () => app(AllocateExamRoomsAction::class)->execute($this->exam, [$this->roomA->id], $override))
        ->toThrow(AuthorizationException::class);
});

test('rooms kept in a new choice keep their invigilators', function () {
    enrol('Alami');
    allocate([$this->roomA->id])->assertOk();
    $lead = User::factory()->teacher()->create();
    app(AssignInvigilatorsAction::class)->execute($this->exam->roomAssignments()->sole(), $lead->id, []);

    allocate([$this->roomB->id, $this->roomA->id])->assertOk()->assertJsonPath('assignments.1.invigilators.0.teacher_id', $lead->id);
});

test('a scheduled exam cannot take a room a course session holds', function () {
    enrol('Alami');
    allocate([$this->roomA->id])->assertOk();
    $this->actingAs($this->coordinator)->post(route('exams.schedule', $this->exam))->assertSessionHasNoErrors();
    CourseSession::factory()->between('2026-10-12 10:00', '2026-10-12 12:00')->create(['room_id' => $this->roomB->id]);

    allocate([$this->roomB->id])->assertUnprocessable()->assertJsonPath('conflicts.0.type', 'room');

    expect($this->exam->roomAssignments()->sole()->room_id)->toBe($this->roomA->id);
});

test('the rooms and invigilators of a scheduled exam block course sessions', function () {
    enrol('Alami');
    allocate([$this->roomA->id])->assertOk();
    $lead = User::factory()->teacher()->create();
    app(AssignInvigilatorsAction::class)->execute($this->exam->roomAssignments()->sole(), $lead->id, []);
    $this->actingAs($this->coordinator)->post(route('exams.schedule', $this->exam))->assertSessionHasNoErrors();

    $module = Module::factory()->create(['program_id' => StudentGroup::factory()->create()->program_id]);
    $payload = fn (array $overrides) => [
        'module_id' => $module->id,
        'teacher_id' => User::factory()->teacher()->create()->id,
        'room_id' => Room::factory()->create()->id,
        'student_group_ids' => [StudentGroup::factory()->create(['program_id' => $module->program_id])->id],
        'starts_at' => '2026-10-12 10:00',
        'ends_at' => '2026-10-12 12:00',
        ...$overrides,
    ];

    $this->actingAs($this->coordinator)->postJson(route('course-sessions.store'), $payload(['room_id' => $this->roomA->id]))
        ->assertUnprocessable()->assertJsonPath('conflicts.0.type', 'room')->assertJsonPath('conflicts.0.booking_type', 'exam');
    $this->actingAs($this->coordinator)->postJson(route('course-sessions.store'), $payload(['teacher_id' => $lead->id]))
        ->assertUnprocessable()->assertJsonPath('conflicts.0.type', 'teacher');
});

test('scheduling needs rooms and candidates, and seats students who joined since', function () {
    $this->actingAs($this->coordinator)->post(route('exams.schedule', $this->exam))->assertSessionHasErrors('exam');

    allocate([$this->roomA->id])->assertOk();
    $this->actingAs($this->coordinator)->post(route('exams.schedule', $this->exam))->assertSessionHasErrors('exam');

    enrol('Alami', 'Benali');
    $this->actingAs($this->coordinator)->post(route('exams.schedule', $this->exam))->assertSessionHasNoErrors();

    expect($this->exam->refresh()->state)->toBe(ExamState::Scheduled)
        ->and($this->exam->candidates()->count())->toBe(2);
});

test('rooms are frozen once the exam is published', function () {
    enrol('Alami');
    allocate([$this->roomA->id])->assertOk();
    $this->exam->update(['state' => ExamState::Published]);

    allocate([$this->roomB->id])->assertUnprocessable()->assertJsonValidationErrors('exam');
});

test('students see their room and seat, and the feed sends them there', function () {
    enrol('Alami');
    allocate([$this->roomA->id])->assertOk();
    $this->exam->update(['state' => ExamState::Published]);
    $student = StudentProfile::sole()->user;

    $this->actingAs($student)->get(route('exams.index'))
        ->assertInertia(fn ($page) => $page->where('exams.0.my_seat.room', 'Amphi A')->where('exams.0.my_seat.seat', 1));

    $token = app(IssueCalendarFeedTokenAction::class)->execute($student);
    $body = str_replace("\r\n ", '', $this->get(route('calendar-feeds.show', ['token' => $token]))->getContent());

    expect($body)->toContain('LOCATION:'.__('messages.calendar_feed_exam_room', ['room' => 'Amphi A', 'seat' => 1]));
});
