<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\AssignInvigilatorsAction;
use App\Actions\Exams\PublishExamAction;
use App\Actions\Exams\RenderConvocationPdfAction;
use App\Actions\Exams\SendUrgentMessageAction;
use App\Jobs\GenerateConvocationJob;
use App\Jobs\GenerateExamRosterJob;
use App\Jobs\SendUrgentMessageJob;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\ExamReschedule;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\SupersededConvocation;
use App\Models\TeacherUnavailability;
use App\Models\User;
use App\Notifications\ExamRescheduledNotification;
use App\Services\UrgentMessages\UrgentMessageGateway;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);
    Storage::fake('local');

    $this->coordinator = User::factory()->coordinator()->create();
    $this->lead = User::factory()->teacher()->create();
    $program = Program::factory()->create();
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->student = StudentProfile::factory()->create(['student_group_id' => $this->group->id, 'phone' => '0612345678'])->user;
    $this->exam = Exam::factory()->scheduled()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($this->group)->create([
        'exam_period_id' => ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create()->id,
        'module_id' => Module::factory()->create(['program_id' => $program->id])->id,
    ]);
    $this->roomA = Room::factory()->create(['name' => 'Amphi A', 'exam_capacity' => 10]);
    app(AllocateExamRoomsAction::class)->execute($this->exam, [$this->roomA->id]);
    app(AssignInvigilatorsAction::class)->execute($this->exam->roomAssignments()->sole(), $this->lead->id, []);
    app(PublishExamAction::class)->execute($this->exam, $this->coordinator);
    $this->candidate = ExamCandidate::sole();
});

function emergencyReschedule(array $overrides = []): TestResponse
{
    return test()->actingAs(test()->coordinator)->post(route('exams.emergency-reschedule', test()->exam), [
        'starts_at' => '2026-10-13 14:00',
        'ends_at' => '2026-10-13 16:00',
        'reason' => 'Inondation du bâtiment A.',
        'confirmed' => true,
        ...$overrides,
    ]);
}

test('a published exam moves to a new time with an audited reason and a new revision', function () {
    emergencyReschedule()->assertSessionHasNoErrors();

    $this->exam->refresh();
    $audit = ExamReschedule::sole();

    expect($this->exam->starts_at->format('Y-m-d H:i'))->toBe('2026-10-13 14:00')
        ->and($this->exam->revision)->toBe(2)
        ->and($audit->reason)->toBe('Inondation du bâtiment A.')
        ->and($audit->user_id)->toBe($this->coordinator->id)
        ->and($audit->previous_starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 09:00')
        ->and($audit->room_ids)->toBe([$this->roomA->id]);
});

test('the coordinator must confirm and give a reason', function () {
    emergencyReschedule(['confirmed' => false, 'reason' => ''])->assertSessionHasErrors(['confirmed', 'reason']);

    expect($this->exam->refresh()->revision)->toBe(1);
});

test('the old convocation is superseded and shows where the student now sits', function () {
    $oldUrl = app(RenderConvocationPdfAction::class)->verificationUrl($this->candidate);
    $oldPdf = $this->candidate->convocationPath();
    $roomB = Room::factory()->create(['name' => 'Salle B', 'exam_capacity' => 10]);

    emergencyReschedule(['room_ids' => [$roomB->id]])->assertSessionHasNoErrors();

    $current = ExamCandidate::sole();

    expect(SupersededConvocation::sole()->convocation_uuid)->toBe($this->candidate->convocation_uuid)
        ->and($current->convocation_uuid)->not->toBe($this->candidate->convocation_uuid)
        ->and(Storage::disk('local')->exists($oldPdf))->toBeFalse()
        ->and(Storage::disk('local')->exists($current->convocationPath()))->toBeTrue();

    $this->actingAs($this->coordinator)->get($oldUrl)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('convocations/superseded')
            ->where('current.room', 'Salle B')
            ->where('exam.revision', 2));

    $this->actingAs($this->coordinator)->get(app(RenderConvocationPdfAction::class)->verificationUrl($current))
        ->assertInertia(fn (Assert $page) => $page->component('convocations/verify'));
});

test('a reschedule regenerates the documents and alerts candidates and invigilators', function () {
    Queue::fake();
    Notification::fake();

    emergencyReschedule()->assertSessionHasNoErrors();

    Queue::assertPushed(GenerateConvocationJob::class, 1);
    Queue::assertPushed(GenerateExamRosterJob::class, 1);
    Queue::assertPushed(SendUrgentMessageJob::class, fn (SendUrgentMessageJob $job) => $job->phone === '0612345678');
    Notification::assertSentTo([$this->student, $this->lead], ExamRescheduledNotification::class);
});

test('urgent messages go through the configured gateway', function () {
    $gateway = Mockery::mock(UrgentMessageGateway::class);
    $gateway->shouldReceive('send')->once()->with('0612345678', Mockery::type('string'));
    $this->app->instance(UrgentMessageGateway::class, $gateway);

    emergencyReschedule()->assertSessionHasNoErrors();
});

test('invigilators busy at the new time are released and told', function () {
    Notification::fake();
    CourseSession::factory()->between('2026-10-13 14:00', '2026-10-13 15:00')->create(['teacher_id' => $this->lead->id]);

    emergencyReschedule()->assertSessionHasNoErrors();

    expect($this->exam->invigilators()->count())->toBe(0)
        ->and(ExamReschedule::sole()->released_invigilator_ids)->toBe([$this->lead->id]);

    Notification::assertSentTo($this->lead, ExamRescheduledNotification::class, fn ($notification) => $notification->place === __('messages.exam_rescheduled_released'));
});

test('a new time that clashes for the groups or rooms is refused, and nothing changes', function () {
    CourseSession::factory()->between('2026-10-13 14:00', '2026-10-13 15:00')->forGroups($this->group)->create();

    emergencyReschedule()->assertSessionHasErrors('conflicts');

    expect($this->exam->refresh()->revision)->toBe(1)
        ->and(SupersededConvocation::count())->toBe(0)
        ->and(ExamCandidate::sole()->convocation_uuid)->toBe($this->candidate->convocation_uuid);
});

test('only published exams that have not started, and only exam managers', function () {
    $this->actingAs($this->lead)->post(route('exams.emergency-reschedule', $this->exam), [
        'starts_at' => '2026-10-13 14:00', 'ends_at' => '2026-10-13 16:00', 'reason' => 'Une raison valable.', 'confirmed' => true,
    ])->assertForbidden();

    $this->travelTo('2026-10-12 09:00');

    emergencyReschedule()->assertSessionHasErrors('exam');
});

test('the exams list shows the revision and its reason', function () {
    emergencyReschedule()->assertSessionHasNoErrors();

    $this->actingAs($this->coordinator)->get(route('exams.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('exams.0.revision', 2)
            ->where('exams.0.last_reschedule_reason', 'Inondation du bâtiment A.'));
});

test('invigilators of rooms the reschedule drops are released, audited and told', function () {
    Notification::fake();
    $roomB = Room::factory()->create(['exam_capacity' => 10]);

    emergencyReschedule(['room_ids' => [$roomB->id]])->assertSessionHasNoErrors();

    expect($this->exam->invigilators()->count())->toBe(0)
        ->and(ExamReschedule::sole()->released_invigilator_ids)->toBe([$this->lead->id]);

    Notification::assertSentTo($this->lead, ExamRescheduledNotification::class, fn ($notification) => $notification->place === __('messages.exam_rescheduled_released'));
});

test('invigilators who declared an unavailability at the new time are released', function () {
    TeacherUnavailability::factory()->create([
        'teacher_id' => $this->lead->id,
        'type' => 'ad_hoc_date',
        'start_date' => '2026-10-13',
        'end_date' => '2026-10-13',
        'start_time' => null,
        'end_time' => null,
        'status' => 'approved',
    ]);

    emergencyReschedule()->assertSessionHasNoErrors();

    expect(ExamReschedule::sole()->released_invigilator_ids)->toBe([$this->lead->id]);
});

test('each urgent message goes out once, even when its job is delivered twice', function () {
    $gateway = Mockery::mock(UrgentMessageGateway::class);
    $gateway->shouldReceive('send')->once();
    $this->app->instance(UrgentMessageGateway::class, $gateway);

    $send = app(SendUrgentMessageAction::class);

    expect($send->execute('exam-1-revision-1-user-1', '0612345678', 'URGENT'))->toBeTrue()
        ->and($send->execute('exam-1-revision-1-user-1', '0612345678', 'URGENT'))->toBeFalse();
});
