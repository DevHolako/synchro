<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\AssignInvigilatorsAction;
use App\Actions\Exams\RenderConvocationPdfAction;
use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);
    Storage::fake('local');

    $this->coordinator = User::factory()->coordinator()->create();
    [$this->leadA, $this->leadB] = User::factory()->teacher()->count(2)->create()->all();
    $program = Program::factory()->create();
    $group = StudentGroup::factory()->create(['program_id' => $program->id]);
    StudentProfile::factory()->create(['student_group_id' => $group->id, 'last_name' => 'Alami']);
    StudentProfile::factory()->create(['student_group_id' => $group->id, 'last_name' => 'Zerouali']);
    $this->exam = Exam::factory()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($group)->create([
        'exam_period_id' => ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create()->id,
        'module_id' => Module::factory()->create(['program_id' => $program->id])->id,
    ]);
    app(AllocateExamRoomsAction::class)->execute($this->exam, [
        Room::factory()->create(['name' => 'Amphi A', 'exam_capacity' => 1])->id,
        Room::factory()->create(['name' => 'Salle B', 'exam_capacity' => 1])->id,
    ]);
    [$this->roomA, $this->roomB] = $this->exam->roomAssignments()->get()->all();
    app(AssignInvigilatorsAction::class)->execute($this->roomA, $this->leadA->id, []);
    app(AssignInvigilatorsAction::class)->execute($this->roomB, $this->leadB->id, []);
    $this->exam->update(['state' => ExamState::Published]);

    $this->alami = ExamCandidate::query()->where('exam_room_assignment_id', $this->roomA->id)->sole();
    $this->zerouali = ExamCandidate::query()->where('exam_room_assignment_id', $this->roomB->id)->sole();
    $this->travelTo('2026-10-12 08:30');
});

function scanUrl(ExamCandidate $candidate): string
{
    return app(RenderConvocationPdfAction::class)->verificationUrl($candidate);
}

test('the room invigilator scans a convocation and marks the candidate present', function () {
    $this->actingAs($this->leadA)->get(scanUrl($this->alami))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('convocations/verify')
            ->where('candidate.room', 'Amphi A')
            ->where('checkIn.can_check_in', true)
            ->where('checkIn.wrong_room', false)
            ->where('viewerRoomId', $this->roomA->id));

    $this->actingAs($this->leadA)->post(route('exam-candidates.check-in.store', $this->alami))->assertSessionHasNoErrors();

    $this->alami->refresh();

    expect($this->alami->checked_in_by)->toBe($this->leadA->id)
        ->and($this->alami->checked_in_at?->format('Y-m-d H:i'))->toBe('2026-10-12 08:30');

    $this->actingAs($this->leadA)->get(scanUrl($this->alami))
        ->assertInertia(fn (Assert $page) => $page
            ->where('checkIn.checked_in_at', '08:30')
            ->where('checkIn.checked_in_by', $this->leadA->name)
            ->where('checkIn.can_check_in', false)
            ->where('checkIn.can_undo', true));
});

test('a second check-in is refused', function () {
    $this->actingAs($this->leadA)->post(route('exam-candidates.check-in.store', $this->alami));

    $this->actingAs($this->coordinator)->post(route('exam-candidates.check-in.store', $this->alami))->assertSessionHasErrors('check_in');

    expect($this->alami->refresh()->checked_in_by)->toBe($this->leadA->id);
});

test('an invigilator of another room is warned and cannot check the candidate in', function () {
    $this->actingAs($this->leadB)->get(scanUrl($this->alami))
        ->assertInertia(fn (Assert $page) => $page
            ->where('checkIn.wrong_room', true)
            ->where('checkIn.can_check_in', false));

    $this->actingAs($this->leadB)->post(route('exam-candidates.check-in.store', $this->alami))->assertSessionHasErrors('check_in');

    expect($this->alami->refresh()->checked_in_at)->toBeNull();
});

test('exam managers check in anyone', function () {
    $this->actingAs($this->coordinator)->post(route('exam-candidates.check-in.store', $this->zerouali))->assertSessionHasNoErrors();

    expect($this->zerouali->refresh()->checked_in_by)->toBe($this->coordinator->id);
});

test('check-in opens an hour before the start and closes at the end', function (string $now, bool $open) {
    $this->travelTo($now);

    $response = $this->actingAs($this->leadA)->post(route('exam-candidates.check-in.store', $this->alami));

    $open ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('check_in');
})->with([
    'too early' => ['2026-10-12 07:59', false],
    'an hour before' => ['2026-10-12 08:00', true],
    'during the exam' => ['2026-10-12 10:59', true],
    'at the end' => ['2026-10-12 11:00', false],
]);

test('a check-in can be cancelled while check-in is open', function () {
    $this->actingAs($this->leadA)->post(route('exam-candidates.check-in.store', $this->alami));

    $this->actingAs($this->leadB)->delete(route('exam-candidates.check-in.destroy', $this->alami))->assertSessionHasErrors('check_in');
    $this->actingAs($this->leadA)->delete(route('exam-candidates.check-in.destroy', $this->alami))->assertSessionHasNoErrors();

    expect($this->alami->refresh()->checked_in_at)->toBeNull()
        ->and($this->alami->checked_in_by)->toBeNull();
});

test('only the exam managers and invigilators reach the check-in screen', function () {
    $this->actingAs(User::factory()->teacher()->create())->get(scanUrl($this->alami))->assertForbidden();
    $this->actingAs($this->alami->student)->get(scanUrl($this->alami))->assertForbidden();
    $this->actingAs(User::factory()->teacher()->create())->post(route('exam-candidates.check-in.store', $this->alami))->assertForbidden();
    $this->actingAs($this->leadA)->get(str_replace('signature=', 'signature=x', scanUrl($this->alami)))->assertForbidden();
});

test('an invigilator opens their own room list, with who is present', function () {
    $this->actingAs($this->leadA)->post(route('exam-candidates.check-in.store', $this->alami));

    $this->actingAs($this->leadA)->get(route('exams.rooms.check-in', [$this->exam, $this->roomA]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exams/room-check-in')
            ->where('open', true)
            ->has('candidates', 1)
            ->where('candidates.0.checked_in_at', '08:30'));

    $this->actingAs($this->leadA)->get(route('exams.rooms.check-in', [$this->exam, $this->roomB]))->assertForbidden();
    $this->actingAs($this->coordinator)->get(route('exams.rooms.check-in', [$this->exam, $this->roomB]))->assertOk();
});

test('an invigilator without the attendance permission cannot check candidates in', function () {
    $student = User::factory()->student()->create();
    $this->exam->invigilators()->create(['exam_room_assignment_id' => $this->roomA->id, 'teacher_id' => $student->id, 'role' => 'adjoint']);

    $this->actingAs($student)->post(route('exam-candidates.check-in.store', $this->alami))->assertForbidden();
    $this->actingAs($student)->get(route('exams.rooms.check-in', [$this->exam, $this->roomA]))->assertForbidden();
});

test('check-in screens show the official name, whatever the display name', function () {
    $this->alami->student->update(['name' => fake()->userName()]);
    $official = $this->alami->student->fresh()->officialName();

    expect($official)->toStartWith('ALAMI ');

    $this->actingAs($this->leadA)->get(route('exams.rooms.check-in', [$this->exam, $this->roomA]))
        ->assertInertia(fn (Assert $page) => $page->where('candidates.0.name', $official));
    $this->actingAs($this->leadA)->get(scanUrl($this->alami))
        ->assertInertia(fn (Assert $page) => $page->where('candidate.name', $official));
});
