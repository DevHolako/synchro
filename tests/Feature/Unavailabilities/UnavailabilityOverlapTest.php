<?php

use App\Enums\UnavailabilityStatus;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-05 09:00:00');
    $this->teacher = User::factory()->teacher()->create();
});

function submitUnavailability(User $teacher, array $payload)
{
    return test()->actingAs($teacher)->post(route('unavailabilities.store'), [
        'start_date' => '2026-10-05',
        'reason' => 'Constraint.',
        ...$payload,
    ]);
}

test('a recurring block overlapping an active one on the same weekday is rejected', function (UnavailabilityStatus $status) {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')->create(['status' => $status]);

    submitUnavailability($this->teacher, [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '11:45', 'end_time' => '13:00',
    ])->assertSessionHasErrors('start_date');

    expect(TeacherUnavailability::count())->toBe(1);
})->with([UnavailabilityStatus::Pending, UnavailabilityStatus::Approved]);

test('recurring blocks that do not overlap are accepted', function (array $payload) {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')->create();

    submitUnavailability($this->teacher, ['type' => 'recurring_weekly', ...$payload])->assertSessionHasNoErrors();

    expect(TeacherUnavailability::count())->toBe(2);
})->with([
    'touching edge' => [['day_of_week' => 5, 'start_time' => '12:00', 'end_time' => '14:00']],
    'another weekday' => [['day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '12:00']],
]);

test('a rejected unavailability does not block a new one', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')->rejected()->create();

    submitUnavailability($this->teacher, [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '12:00',
    ])->assertSessionHasNoErrors();
});

test('another teacher unavailability never blocks', function () {
    TeacherUnavailability::factory()->recurring(5, '10:00', '12:00')->create();

    submitUnavailability($this->teacher, [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '12:00',
    ])->assertSessionHasNoErrors();
});

test('recurring periods only overlap when their dates intersect', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')
        ->create(['start_date' => '2026-10-05', 'end_date' => '2026-12-31']);

    submitUnavailability($this->teacher, [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '12:00',
        'start_date' => '2027-01-01',
    ])->assertSessionHasNoErrors();

    submitUnavailability($this->teacher, [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '12:00',
        'start_date' => '2026-12-31', 'end_date' => '2027-01-01',
    ])->assertSessionHasErrors('start_date');
});

test('an open-ended recurring block overlaps any later period', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')->create();

    submitUnavailability($this->teacher, [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '10:15',
        'start_date' => '2030-01-01',
    ])->assertSessionHasErrors('start_date');
});

test('overlapping ad-hoc ranges are rejected', function (array $existing, array $payload) {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc(...$existing)->create();

    submitUnavailability($this->teacher, ['type' => 'ad_hoc_date', ...$payload])->assertSessionHasErrors('start_date');
})->with([
    'whole days inside whole days' => [['2026-11-01', '2026-11-07'], ['start_date' => '2026-11-03', 'end_date' => '2026-11-03']],
    'time window inside whole days' => [['2026-11-01', '2026-11-07'], ['start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'start_time' => '14:00', 'end_time' => '16:00']],
    'whole days over a time window' => [['2026-11-03', '2026-11-03', '14:00', '16:00'], ['start_date' => '2026-11-03', 'end_date' => '2026-11-04']],
    'overlapping time windows' => [['2026-11-03', '2026-11-05', '14:00', '16:00'], ['start_date' => '2026-11-05', 'end_date' => '2026-11-06', 'start_time' => '15:45', 'end_time' => '17:00']],
    'shared boundary day' => [['2026-11-01', '2026-11-03'], ['start_date' => '2026-11-03', 'end_date' => '2026-11-04']],
]);

test('ad-hoc ranges that do not overlap are accepted', function (array $existing, array $payload) {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc(...$existing)->create();

    submitUnavailability($this->teacher, ['type' => 'ad_hoc_date', ...$payload])->assertSessionHasNoErrors();
})->with([
    'later days' => [['2026-11-01', '2026-11-03'], ['start_date' => '2026-11-04', 'end_date' => '2026-11-05']],
    'touching time windows' => [['2026-11-03', '2026-11-03', '10:00', '12:00'], ['start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'start_time' => '12:00', 'end_time' => '14:00']],
]);

test('recurring and ad-hoc unavailabilities are not compared with each other', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '18:00', '22:00')->create();

    // 2026-11-06 is a Friday.
    submitUnavailability($this->teacher, [
        'type' => 'ad_hoc_date', 'start_date' => '2026-11-02', 'end_date' => '2026-11-08',
    ])->assertSessionHasNoErrors();
});

test('editing an unavailability does not conflict with itself', function () {
    $unavailability = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')->create();

    $this->actingAs($this->teacher)->put(route('unavailabilities.update', $unavailability), [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '13:00',
        'start_date' => '2026-10-05', 'reason' => 'Longer now.',
    ])->assertSessionHasNoErrors();

    expect($unavailability->refresh()->end_time)->toBe('13:00');
});

test('editing into an overlap with another request is rejected', function () {
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '14:00', '16:00')->create();
    $unavailability = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->recurring(5, '10:00', '12:00')->create();

    $this->actingAs($this->teacher)->put(route('unavailabilities.update', $unavailability), [
        'type' => 'recurring_weekly', 'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '15:00',
        'start_date' => '2026-10-05', 'reason' => 'Longer now.',
    ])->assertSessionHasErrors('start_date');

    expect($unavailability->refresh()->end_time)->toBe('12:00');
});
