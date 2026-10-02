<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\AssignInvigilatorsAction;
use App\Actions\Exams\RenderConvocationPdfAction;
use App\Actions\Exams\StoreConvocationAction;
use App\Enums\ExamState;
use App\Jobs\GenerateConvocationJob;
use App\Jobs\GenerateExamRosterJob;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Documents\QrCode;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);
    Storage::fake('local');

    $this->coordinator = User::factory()->coordinator()->create();
    $this->invigilator = User::factory()->teacher()->create();
    $program = Program::factory()->create();
    $group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->students = StudentProfile::factory()->count(3)->create(['student_group_id' => $group->id])
        ->map(fn (StudentProfile $profile) => $profile->user);
    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();
    $this->exam = Exam::factory()->scheduled()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($group)->create([
        'exam_period_id' => $this->period->id,
        'module_id' => Module::factory()->create(['program_id' => $program->id, 'code' => 'ALG-101'])->id,
    ]);
    app(AllocateExamRoomsAction::class)->execute($this->exam, [Room::factory()->create(['exam_capacity' => 10])->id]);
    app(AssignInvigilatorsAction::class)->execute($this->exam->roomAssignments()->sole(), $this->invigilator->id, []);
});

function candidateOf(User $student): ExamCandidate
{
    return ExamCandidate::query()->where('student_id', $student->id)->sole();
}

test('publishing an exam queues one convocation per candidate and the room sheets', function () {
    Queue::fake();

    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam))->assertSessionHasNoErrors();

    Queue::assertPushedOn('default', GenerateConvocationJob::class);
    Queue::assertPushed(GenerateConvocationJob::class, 3);
    Queue::assertPushed(GenerateExamRosterJob::class, fn (GenerateExamRosterJob $job) => $job->exam->is($this->exam));
});

test('publishing a period queues the documents of the exams it publishes', function () {
    Queue::fake();

    $this->actingAs($this->coordinator)->post(route('exam-periods.publish', $this->period))->assertSessionHasNoErrors();

    Queue::assertPushed(GenerateConvocationJob::class, 3);
    Queue::assertPushed(GenerateExamRosterJob::class, 1);
});

test('the jobs store a PDF convocation per candidate and the room sheets', function () {
    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam))->assertSessionHasNoErrors();

    foreach ($this->students as $student) {
        expect(Storage::disk('local')->get(candidateOf($student)->convocationPath()))->toStartWith('%PDF');
    }

    expect(Storage::disk('local')->get($this->exam->rosterPath()))->toStartWith('%PDF');
});

test('generating a convocation twice writes it once', function () {
    $candidate = candidateOf($this->students[0]);

    expect(app(StoreConvocationAction::class)->execute($candidate))->toBeTrue()
        ->and(app(StoreConvocationAction::class)->execute($candidate))->toBeFalse();
});

test('the QR code carries the signed verification link', function () {
    $url = app(RenderConvocationPdfAction::class)->verificationUrl(candidateOf($this->students[0]));
    $svg = base64_decode(str_replace('data:image/svg+xml;base64,', '', QrCode::svgDataUri($url)));

    expect($url)->toContain('/verify/convocation/'.candidateOf($this->students[0])->convocation_uuid)
        ->toContain('signature=')
        ->and($svg)->toStartWith('<?xml')->toContain('<svg');
});

test('the exam invigilators and managers open a scanned convocation; a tampered link is refused', function () {
    $student = $this->students[0];
    $url = app(RenderConvocationPdfAction::class)->verificationUrl(candidateOf($student));

    $this->actingAs($this->invigilator)->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('convocations/verify')
            ->where('candidate.name', $student->officialName())
            ->where('candidate.seat', candidateOf($student)->seat_number));

    $this->actingAs($this->coordinator)->get($url)->assertOk();
    $this->actingAs($this->invigilator)->get(str_replace('signature=', 'signature=0', $url))->assertForbidden();
    $this->actingAs(User::factory()->teacher()->create())->get($url)->assertForbidden();

    auth()->guard('web')->logout();

    $this->get($url)->assertRedirect(route('login'));
});

test('students download their own convocation once the exam is published', function () {
    $this->actingAs($this->students[0])->get(route('exams.convocation', $this->exam))->assertForbidden();

    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam));

    $this->actingAs($this->students[0])->get(route('exams.convocation', $this->exam))
        ->assertOk()
        ->assertDownload('convocation-ALG-101.pdf');

    $this->actingAs(User::factory()->student()->create())->get(route('exams.convocation', $this->exam))->assertForbidden();
});

test('a convocation still being prepared sends the student back', function () {
    $this->exam->update(['state' => ExamState::Published]);

    $this->actingAs($this->students[0])->from(route('exams.index'))
        ->get(route('exams.convocation', $this->exam))
        ->assertRedirect(route('exams.index'));
});

test('exam managers and the exam invigilators download the room sheets, students do not', function () {
    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam));

    $this->actingAs($this->coordinator)->get(route('exams.roster', $this->exam))->assertDownload('emargement-ALG-101.pdf');
    $this->actingAs($this->invigilator)->get(route('exams.roster', $this->exam))->assertOk();
    $this->actingAs($this->students[0])->get(route('exams.roster', $this->exam))->assertForbidden();
});

test('the exams list tells students when their convocation is ready', function () {
    $this->actingAs($this->coordinator)->post(route('exams.publish', $this->exam));

    $this->actingAs($this->students[0])->get(route('exams.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('exams.0.my_seat.convocation_ready', true)
            ->where('exams.0.roster', null));

    $this->actingAs($this->invigilator)->get(route('exams.index'))
        ->assertInertia(fn (Assert $page) => $page->where('exams.0.roster', 'ready'));
});

test('the convocation prints the official name and the QR code of its signed link', function () {
    $student = $this->students[0];
    $student->studentProfile->update(['last_name' => 'El Amrani', 'first_name' => 'Youssef']);
    $student->update(['name' => 'yoyo']);
    $candidate = candidateOf($student);
    $render = app(RenderConvocationPdfAction::class);

    $data = $render->viewData($candidate->fresh());

    expect($data['name'])->toBe('EL AMRANI Youssef')
        ->and($data['qrCode'])->toBe(QrCode::svgDataUri($render->verificationUrl($candidate)));
});

test('a signed link to an unknown convocation is not found', function () {
    $this->actingAs($this->coordinator)
        ->get(URL::signedRoute('convocations.verify', ['uuid' => fake()->uuid()]))
        ->assertNotFound();
});
