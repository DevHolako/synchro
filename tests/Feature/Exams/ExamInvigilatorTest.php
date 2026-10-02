<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Enums\ConflictType;
use App\Enums\ExamState;
use App\Enums\InvigilatorRole;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\ExamRoomAssignment;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
    StudentProfile::factory()->count(2)->create(['student_group_id' => $this->group->id]);
    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();
    $this->exam = Exam::factory()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($this->group)->create([
        'exam_period_id' => $this->period->id,
        'module_id' => Module::factory()->create(['program_id' => $program->id])->id,
    ]);
    app(AllocateExamRoomsAction::class)->execute($this->exam, [
        Room::factory()->create(['exam_capacity' => 1])->id,
        Room::factory()->create(['exam_capacity' => 1])->id,
    ]);
    [$this->roomA, $this->roomB] = $this->exam->roomAssignments()->get()->all();
    $this->teachers = User::factory()->teacher()->count(3)->create();
});

function staff(ExamRoomAssignment $room, int $leadId, array $assistantIds = [], array $extra = []): TestResponse
{
    return test()->actingAs(test()->coordinator)->putJson(
        route('exams.invigilators.update', [test()->exam, $room]),
        ['lead_id' => $leadId, 'assistant_ids' => $assistantIds, ...$extra],
    );
}

test('a room gets one lead invigilator and any number of assistants', function () {
    staff($this->roomA, $this->teachers[0]->id, [$this->teachers[1]->id, $this->teachers[2]->id])
        ->assertOk()
        ->assertJsonCount(3, 'assignments.0.invigilators');

    expect($this->roomA->invigilators()->where('role', InvigilatorRole::Principal)->sole()->teacher_id)->toBe($this->teachers[0]->id);

    staff($this->roomA, $this->teachers[1]->id)->assertOk()->assertJsonCount(1, 'assignments.0.invigilators');
});

test('the lead cannot also be an assistant, and only teachers invigilate', function () {
    staff($this->roomA, $this->teachers[0]->id, [$this->teachers[0]->id])->assertJsonValidationErrors('assistant_ids.0');
    staff($this->roomA, $this->coordinator->id)->assertJsonValidationErrors('lead_id');
});

test('a teacher watches one room of an exam at most', function () {
    staff($this->roomA, $this->teachers[0]->id)->assertOk();

    staff($this->roomB, $this->teachers[1]->id, [$this->teachers[0]->id])->assertJsonValidationErrors('assistant_ids');
});

test('once the exam is booked, an invigilator teaching at the same time is refused; a draft is not checked', function () {
    CourseSession::factory()->between('2026-10-12 10:00', '2026-10-12 12:00')->create(['teacher_id' => $this->teachers[0]->id]);

    staff($this->roomA, $this->teachers[0]->id)->assertOk();

    $this->exam->update(['state' => ExamState::Scheduled]);

    staff($this->roomB, $this->teachers[0]->id)->assertJsonValidationErrors('assistant_ids');
    staff($this->roomA, $this->teachers[0]->id)->assertUnprocessable()->assertJsonPath('conflicts.0.type', 'teacher');
});

test('an invigilator invigilating another booked exam at the same time is refused', function () {
    $other = Exam::factory()->scheduled()->between('2026-10-12 10:00', '2026-10-12 12:00')->create(['exam_period_id' => $this->period->id]);
    $otherRoom = $other->roomAssignments()->create(['room_id' => Room::factory()->create()->id, 'position' => 0]);
    $otherRoom->invigilators()->create(['exam_id' => $other->id, 'teacher_id' => $this->teachers[0]->id, 'role' => InvigilatorRole::Principal]);
    $this->exam->update(['state' => ExamState::Scheduled]);

    staff($this->roomA, $this->teachers[0]->id)->assertUnprocessable()->assertJsonPath('conflicts.0.booking_type', 'exam');
});

test('a declared unavailability needs a justified override, which is audited', function () {
    TeacherUnavailability::factory()->create([
        'teacher_id' => $this->teachers[0]->id,
        'type' => 'ad_hoc_date',
        'start_date' => '2026-10-12',
        'end_date' => '2026-10-12',
        'start_time' => null,
        'end_time' => null,
        'status' => 'approved',
    ]);

    staff($this->roomA, $this->teachers[0]->id)->assertStatus(409)->assertJsonPath('soft_conflicts.0.type', 'unavailability');

    staff($this->roomA, $this->teachers[0]->id, [], ['force_override' => true, 'justification' => 'Remplacement validé par le chef de département.'])
        ->assertOk();

    expect(ConflictOverride::sole()->conflict_type)->toBe(ConflictType::Unavailability);
});

test('invigilators may change after publication, until the exam starts', function () {
    $this->exam->update(['state' => ExamState::Published]);

    staff($this->roomA, $this->teachers[0]->id)->assertOk();

    $this->travelTo('2026-10-12 09:00');

    staff($this->roomA, $this->teachers[1]->id)->assertJsonValidationErrors('exam');
});

test('publishing needs a lead invigilator in every room', function () {
    $this->exam->update(['state' => ExamState::Scheduled]);
    staff($this->roomA, $this->teachers[0]->id)->assertOk();

    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam))->assertSessionHasErrors('exam');

    staff($this->roomB, $this->teachers[1]->id)->assertOk();

    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam))->assertSessionHasNoErrors();

    expect($this->exam->refresh()->state)->toBe(ExamState::Published);
});

test('publishing a period leaves out the exams still missing a lead', function () {
    $this->exam->update(['state' => ExamState::Scheduled]);
    $ready = Exam::factory()->scheduled()->between('2026-10-13 09:00', '2026-10-13 11:00')->create(['exam_period_id' => $this->period->id]);
    $readyRoom = $ready->roomAssignments()->create(['room_id' => Room::factory()->create()->id, 'position' => 0]);
    $readyRoom->invigilators()->create(['exam_id' => $ready->id, 'teacher_id' => $this->teachers[2]->id, 'role' => InvigilatorRole::Principal]);

    $this->actingAs($this->coordinator)->post(route('exam-periods.publish', $this->period))->assertSessionHasNoErrors();

    expect($ready->refresh()->state)->toBe(ExamState::Published)
        ->and($this->exam->refresh()->state)->toBe(ExamState::Scheduled);
});

test('invigilators see the published exams they watch, with their room and role', function () {
    staff($this->roomB, $this->teachers[0]->id)->assertOk();
    $this->exam->update(['state' => ExamState::Published]);

    $this->actingAs($this->teachers[0])->get(route('exams.index'))
        ->assertInertia(fn ($page) => $page
            ->has('exams', 1)
            ->where('exams.0.my_invigilation', ['assignment_id' => $this->roomB->id, 'room' => $this->roomB->room->name, 'role' => 'principal']));

    $this->actingAs($this->teachers[1])->get(route('exams.index'))->assertInertia(fn ($page) => $page->has('exams', 0));
});

test('moving a scheduled exam releases the invigilators busy or unavailable then, unless overridden', function () {
    staff($this->roomA, $this->teachers[0]->id)->assertOk();
    staff($this->roomB, $this->teachers[1]->id)->assertOk();
    $this->exam->update(['state' => ExamState::Scheduled]);
    CourseSession::factory()->between('2026-10-14 09:00', '2026-10-14 10:00')->create(['teacher_id' => $this->teachers[0]->id]);
    $unavailable = TeacherUnavailability::factory()->create([
        'teacher_id' => $this->teachers[1]->id,
        'type' => 'ad_hoc_date',
        'start_date' => '2026-10-14',
        'end_date' => '2026-10-14',
        'start_time' => null,
        'end_time' => null,
        'status' => 'approved',
    ]);
    ConflictOverride::create([
        'user_id' => $this->coordinator->id,
        'schedulable_type' => 'exam',
        'schedulable_id' => $this->exam->id,
        'conflict_type' => ConflictType::Unavailability,
        'justification' => 'Remplacement accepté par le département.',
        'details' => ['unavailability_id' => $unavailable->id],
    ]);

    $this->actingAs($this->coordinator)->put(route('exams.update', $this->exam), [
        'exam_period_id' => $this->period->id,
        'module_id' => $this->exam->module_id,
        'student_group_ids' => [$this->group->id],
        'starts_at' => '2026-10-14 09:00',
        'ends_at' => '2026-10-14 11:00',
    ])->assertSessionHasNoErrors();

    expect($this->exam->invigilators()->pluck('teacher_id')->all())->toBe([$this->teachers[1]->id]);
});

test('the exams list runs the same number of queries however many exams an invigilator watches', function () {
    $queriesFor = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->teachers[0]->fresh())->get(route('exams.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };
    $watchedExam = function (string $day): void {
        $exam = Exam::factory()->published()->between("{$day} 09:00", "{$day} 11:00")->create(['exam_period_id' => $this->period->id]);
        $room = $exam->roomAssignments()->create(['room_id' => Room::factory()->create()->id, 'position' => 0]);
        $room->invigilators()->create(['exam_id' => $exam->id, 'teacher_id' => $this->teachers[0]->id, 'role' => InvigilatorRole::Principal]);
    };

    $watchedExam('2026-10-20');
    $one = $queriesFor();

    $watchedExam('2026-10-21');
    $watchedExam('2026-10-22');

    expect($queriesFor())->toBe($one);
});
