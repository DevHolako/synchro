<?php

use App\Enums\ExamPeriodStatus;
use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\User;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);

    $this->coordinator = User::factory()->coordinator()->create();
    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();
});

function periodPayload(array $overrides = []): array
{
    return [
        'name' => 'Session normale S1',
        'session_type' => 'normal',
        'academic_year' => '2026-2027',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
        ...$overrides,
    ];
}

function periodExam(ExamPeriod $period, string $startsAt, ExamState $state = ExamState::Draft): Exam
{
    return Exam::factory()->between($startsAt, substr($startsAt, 0, 11).'23:00')->create([
        'exam_period_id' => $period->id,
        'state' => $state,
    ]);
}

test('coordinators create a period and land on it', function () {
    $this->actingAs($this->coordinator)
        ->post(route('exam-periods.store'), periodPayload(['name' => 'Rattrapage S1', 'session_type' => 'rattrapage']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('exams.index', ['period' => ExamPeriod::latest('id')->value('id')]));

    expect(ExamPeriod::where('name', 'Rattrapage S1')->sole()->session_type->value)->toBe('rattrapage');
});

test('a period ends on or after it starts', function () {
    $this->actingAs($this->coordinator)
        ->post(route('exam-periods.store'), periodPayload(['end_date' => '2026-09-30']))
        ->assertSessionHasErrors('end_date');
});

test('a period cannot be re-dated so that some of its exams fall outside', function () {
    periodExam($this->period, '2026-10-20 09:00');

    $this->actingAs($this->coordinator)
        ->put(route('exam-periods.update', $this->period), periodPayload(['end_date' => '2026-10-19']))
        ->assertSessionHasErrors('start_date');

    $this->actingAs($this->coordinator)
        ->put(route('exam-periods.update', $this->period), periodPayload(['start_date' => '2026-10-20', 'end_date' => '2026-10-20']))
        ->assertSessionHasNoErrors();

    expect($this->period->refresh()->start_date->format('Y-m-d'))->toBe('2026-10-20');
});

test('only an empty period can be deleted', function () {
    $exam = periodExam($this->period, '2026-10-20 09:00');

    $this->actingAs($this->coordinator)->delete(route('exam-periods.destroy', $this->period))->assertSessionHasErrors('period');

    $exam->delete();

    $this->actingAs($this->coordinator)->delete(route('exam-periods.destroy', $this->period))->assertSessionHasNoErrors();

    expect(ExamPeriod::count())->toBe(0);
});

test('publishing a period publishes its upcoming scheduled exams only', function () {
    $scheduled = periodExam($this->period, '2026-10-20 09:00', ExamState::Scheduled);
    $draft = periodExam($this->period, '2026-10-21 09:00');
    $overdue = periodExam($this->period, '2026-10-02 09:00', ExamState::Scheduled);

    $this->actingAs($this->coordinator)->post(route('exam-periods.publish', $this->period))->assertSessionHasNoErrors();

    expect($scheduled->refresh()->state)->toBe(ExamState::Published)
        ->and($scheduled->published_by)->toBe($this->coordinator->id)
        ->and($draft->refresh()->state)->toBe(ExamState::Draft)
        ->and($overdue->refresh()->state)->toBe(ExamState::Scheduled);

    $this->actingAs($this->coordinator)->post(route('exam-periods.publish', $this->period))->assertSessionHasErrors('period');
});

test('a period is archived once all its exams are completed', function () {
    $completed = periodExam($this->period, '2026-10-02 09:00', ExamState::Completed);
    $published = periodExam($this->period, '2026-10-20 09:00', ExamState::Published);

    $this->actingAs($this->coordinator)->post(route('exam-periods.archive', $this->period))->assertSessionHasErrors('period');

    $published->update(['state' => ExamState::Completed]);

    $this->actingAs($this->coordinator)->post(route('exam-periods.archive', $this->period))->assertSessionHasNoErrors();

    expect($completed->refresh()->state)->toBe(ExamState::Archived)
        ->and($this->period->status())->toBe(ExamPeriodStatus::Archived);

    $this->actingAs($this->coordinator)->post(route('exam-periods.archive', $this->period))->assertSessionHasErrors('period');
});

test('a period status follows the school calendar', function (string $start, string $end, ExamPeriodStatus $status) {
    expect(ExamPeriod::factory()->between($start, $end)->create()->status())->toBe($status);
})->with([
    'upcoming' => ['2026-10-06', '2026-10-20', ExamPeriodStatus::Upcoming],
    'ongoing on its first day' => ['2026-10-05', '2026-10-20', ExamPeriodStatus::Ongoing],
    'ongoing on its last day' => ['2026-09-20', '2026-10-05', ExamPeriodStatus::Ongoing],
    'ended' => ['2026-09-01', '2026-10-04', ExamPeriodStatus::Ended],
]);

test('only exam managers manage periods', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->post(route('exam-periods.store'), periodPayload())->assertForbidden();
    $this->actingAs($teacher)->post(route('exam-periods.publish', $this->period))->assertForbidden();
    $this->actingAs($teacher)->delete(route('exam-periods.destroy', $this->period))->assertForbidden();
});
