<?php

use App\Enums\UnavailabilityStatus;
use App\Enums\UnavailabilityType;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-05 09:00:00'); // a Monday
    $this->teacher = User::factory()->teacher()->create();
});

function recurringPayload(array $overrides = []): array
{
    return [
        'type' => UnavailabilityType::RecurringWeekly->value,
        'day_of_week' => 5,
        'start_date' => '2026-10-05',
        'end_date' => null,
        'start_time' => '18:00',
        'end_time' => '22:00',
        'reason' => 'Professional obligation every Friday evening.',
        ...$overrides,
    ];
}

function adHocPayload(array $overrides = []): array
{
    return [
        'type' => UnavailabilityType::AdHocDate->value,
        'start_date' => '2026-11-03',
        'end_date' => '2026-11-05',
        'reason' => 'Conference in Rabat.',
        ...$overrides,
    ];
}

test('teachers can declare a recurring weekly unavailability as pending', function () {
    $this->actingAs($this->teacher)
        ->post(route('unavailabilities.store'), recurringPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $unavailability = TeacherUnavailability::sole();

    expect($unavailability->teacher_id)->toBe($this->teacher->id)
        ->and($unavailability->type)->toBe(UnavailabilityType::RecurringWeekly)
        ->and($unavailability->status)->toBe(UnavailabilityStatus::Pending)
        ->and($unavailability->day_of_week)->toBe(5)
        ->and($unavailability->start_time)->toBe('18:00')
        ->and($unavailability->end_time)->toBe('22:00')
        ->and($unavailability->end_date)->toBeNull();
});

test('teachers can declare a whole-day ad-hoc unavailability', function () {
    $this->actingAs($this->teacher)
        ->post(route('unavailabilities.store'), adHocPayload(['day_of_week' => null]))
        ->assertSessionHasNoErrors();

    $unavailability = TeacherUnavailability::sole();

    expect($unavailability->type)->toBe(UnavailabilityType::AdHocDate)
        ->and($unavailability->day_of_week)->toBeNull()
        ->and($unavailability->start_date->toDateString())->toBe('2026-11-03')
        ->and($unavailability->end_date->toDateString())->toBe('2026-11-05')
        ->and($unavailability->start_time)->toBeNull();
});

test('teachers can declare an ad-hoc unavailability with a daily time window', function () {
    $this->actingAs($this->teacher)
        ->post(route('unavailabilities.store'), adHocPayload(['start_time' => '14:00', 'end_time' => '18:00']))
        ->assertSessionHasNoErrors();

    expect(TeacherUnavailability::sole()->start_time)->toBe('14:00');
});

test('declarations are validated', function (array $payload, string $field) {
    $this->actingAs($this->teacher)
        ->post(route('unavailabilities.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(TeacherUnavailability::count())->toBe(0);
})->with([
    'start date in the past' => [fn () => recurringPayload(['start_date' => '2026-10-04']), 'start_date'],
    'recurring without weekday' => [fn () => recurringPayload(['day_of_week' => null]), 'day_of_week'],
    'weekday out of range' => [fn () => recurringPayload(['day_of_week' => 8]), 'day_of_week'],
    'recurring without times' => [fn () => recurringPayload(['start_time' => null, 'end_time' => null]), 'start_time'],
    'time off the quarter hour' => [fn () => recurringPayload(['start_time' => '18:10']), 'start_time'],
    'time before the grid' => [fn () => recurringPayload(['start_time' => '07:45']), 'start_time'],
    'time after the grid' => [fn () => recurringPayload(['end_time' => '22:15']), 'end_time'],
    'end time not after start time' => [fn () => recurringPayload(['start_time' => '18:00', 'end_time' => '18:00']), 'end_time'],
    'ad-hoc without end date' => [fn () => adHocPayload(['end_date' => null]), 'end_date'],
    'ad-hoc with a weekday' => [fn () => adHocPayload(['day_of_week' => 3]), 'day_of_week'],
    'ad-hoc end date before start date' => [fn () => adHocPayload(['end_date' => '2026-11-02']), 'end_date'],
    'ad-hoc with only one time' => [fn () => adHocPayload(['start_time' => '14:00']), 'end_time'],
    'missing reason' => [fn () => recurringPayload(['reason' => '']), 'reason'],
    'reason too long' => [fn () => recurringPayload(['reason' => str_repeat('a', 501)]), 'reason'],
]);

test('only users holding the declare permission can declare or list their unavailabilities', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->post(route('unavailabilities.store'), recurringPayload())->assertForbidden();
    $this->actingAs($user)->get(route('unavailabilities.index'))->assertForbidden();
})->with(['coordinator', 'student']);

test('the teacher page lists only the teacher own current and upcoming unavailabilities by default', function () {
    $current = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->create();
    $past = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->adHoc('2026-09-01', '2026-09-02')->create();
    TeacherUnavailability::factory()->create();

    $this->actingAs($this->teacher)->get(route('unavailabilities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('unavailabilities/index')
            ->has('unavailabilities', 1)
            ->where('unavailabilities.0.id', $current->id)
            ->where('filters.period', 'current'));

    $this->actingAs($this->teacher)->get(route('unavailabilities.index', ['period' => 'past']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('unavailabilities', 1)
            ->where('unavailabilities.0.id', $past->id)
            ->where('unavailabilities.0.start_date', '2026-09-01'));
});
