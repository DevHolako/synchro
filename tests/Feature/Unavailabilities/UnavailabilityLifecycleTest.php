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
    $this->payload = [
        'type' => 'recurring_weekly', 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '10:00',
        'start_date' => '2026-10-06', 'reason' => 'Updated reason.',
    ];
});

test('teachers can edit their pending unavailability', function () {
    $unavailability = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->create();

    $this->actingAs($this->teacher)->put(route('unavailabilities.update', $unavailability), $this->payload)
        ->assertSessionHasNoErrors();

    expect($unavailability->refresh())
        ->day_of_week->toBe(2)
        ->reason->toBe('Updated reason.')
        ->status->toBe(UnavailabilityStatus::Pending);
});

test('reviewed unavailabilities cannot be edited', function (string $state) {
    $unavailability = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->{$state}()->create();

    $this->actingAs($this->teacher)->put(route('unavailabilities.update', $unavailability), $this->payload)
        ->assertSessionHasErrors('status');

    expect($unavailability->refresh()->reason)->not->toBe('Updated reason.');
})->with(['approved', 'rejected']);

test('teachers can delete their unavailability in any status', function (UnavailabilityStatus $status) {
    $unavailability = TeacherUnavailability::factory()->for($this->teacher, 'teacher')->create(['status' => $status]);

    $this->actingAs($this->teacher)->delete(route('unavailabilities.destroy', $unavailability))->assertRedirect();

    expect(TeacherUnavailability::count())->toBe(0);
})->with(UnavailabilityStatus::cases());

test('teachers cannot edit or delete another teacher unavailability', function () {
    $unavailability = TeacherUnavailability::factory()->create();

    $this->actingAs($this->teacher)->put(route('unavailabilities.update', $unavailability), $this->payload)->assertForbidden();
    $this->actingAs($this->teacher)->delete(route('unavailabilities.destroy', $unavailability))->assertForbidden();

    expect(TeacherUnavailability::count())->toBe(1);
});

test('deleting a teacher deletes their unavailabilities and keeps reviewed ones when the reviewer leaves', function () {
    $coordinator = User::factory()->coordinator()->create();
    $kept = TeacherUnavailability::factory()->approved()->create(['reviewed_by' => $coordinator->id]);
    TeacherUnavailability::factory()->for($this->teacher, 'teacher')->create();

    $this->teacher->delete();
    $coordinator->delete();

    expect(TeacherUnavailability::count())->toBe(1)
        ->and($kept->refresh()->reviewed_by)->toBeNull()
        ->and($kept->status)->toBe(UnavailabilityStatus::Approved);
});
