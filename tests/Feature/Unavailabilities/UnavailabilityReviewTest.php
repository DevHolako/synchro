<?php

use App\Actions\Unavailabilities\ReviewUnavailabilityAction;
use App\Enums\Permission;
use App\Enums\UnavailabilityStatus;
use App\Enums\UserRole;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
});

test('coordinators can approve a pending unavailability', function () {
    $unavailability = TeacherUnavailability::factory()->create();

    $this->actingAs($this->coordinator)
        ->patch(route('unavailability-reviews.update', $unavailability), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($unavailability->refresh())
        ->status->toBe(UnavailabilityStatus::Approved)
        ->reviewed_by->toBe($this->coordinator->id)
        ->reviewed_at->not->toBeNull()
        ->review_note->toBeNull();
});

test('rejecting requires a note that is stored for the teacher', function () {
    $unavailability = TeacherUnavailability::factory()->create();

    $this->actingAs($this->coordinator)
        ->patch(route('unavailability-reviews.update', $unavailability), ['decision' => 'rejected', 'review_note' => ''])
        ->assertSessionHasErrors('review_note');

    expect($unavailability->refresh()->status)->toBe(UnavailabilityStatus::Pending);

    $this->actingAs($this->coordinator)
        ->patch(route('unavailability-reviews.update', $unavailability), ['decision' => 'rejected', 'review_note' => 'Fridays are needed for exams.'])
        ->assertSessionHasNoErrors();

    expect($unavailability->refresh())
        ->status->toBe(UnavailabilityStatus::Rejected)
        ->review_note->toBe('Fridays are needed for exams.');
});

test('decisions are final', function (string $state) {
    $unavailability = TeacherUnavailability::factory()->{$state}()->create();
    $original = $unavailability->status;

    $this->actingAs($this->coordinator)
        ->patch(route('unavailability-reviews.update', $unavailability), ['decision' => 'rejected', 'review_note' => 'Changed my mind.'])
        ->assertSessionHasErrors('decision');

    expect($unavailability->refresh()->status)->toBe($original);
})->with(['approved', 'rejected']);

test('a stale model cannot overwrite a decision taken in the meantime', function () {
    $unavailability = TeacherUnavailability::factory()->create();
    $stale = TeacherUnavailability::find($unavailability->id);
    $action = app(ReviewUnavailabilityAction::class);

    $action->execute($unavailability, $this->coordinator, UnavailabilityStatus::Approved);

    expect(fn () => $action->execute($stale, $this->coordinator, UnavailabilityStatus::Rejected, 'Too late.'))
        ->toThrow(ValidationException::class);
    expect($unavailability->refresh()->status)->toBe(UnavailabilityStatus::Approved);
});

test('only users holding the review permission can list or review unavailabilities', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $unavailability = TeacherUnavailability::factory()->create();

    $this->actingAs($user)->get(route('unavailability-reviews.index'))->assertForbidden();
    $this->actingAs($user)
        ->patch(route('unavailability-reviews.update', $unavailability), ['decision' => 'approved'])
        ->assertForbidden();

    expect($unavailability->refresh()->status)->toBe(UnavailabilityStatus::Pending);
})->with(['teacher', 'student']);

test('the review page lists pending unavailabilities by default with status counts', function () {
    $pending = TeacherUnavailability::factory()->create();
    TeacherUnavailability::factory()->approved()->create();
    TeacherUnavailability::factory()->rejected()->create();

    $this->actingAs($this->coordinator)->get(route('unavailability-reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('unavailability-reviews/index')
            ->has('unavailabilities.data', 1)
            ->where('unavailabilities.data.0.id', $pending->id)
            ->where('unavailabilities.data.0.teacher.name', $pending->teacher->name)
            ->where('filters.status', 'pending')
            ->where('stats', ['pending' => 1, 'approved' => 1, 'rejected' => 1]));

    $this->actingAs($this->coordinator)->get(route('unavailability-reviews.index', ['status' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->has('unavailabilities.data', 3));
});

test('the review page filters by teacher and type', function () {
    $teacher = User::factory()->teacher()->create();
    $match = TeacherUnavailability::factory()->for($teacher, 'teacher')->adHoc('2026-11-03', '2026-11-04')->create();
    TeacherUnavailability::factory()->for($teacher, 'teacher')->create();
    TeacherUnavailability::factory()->adHoc('2026-11-03', '2026-11-04')->create();

    $this->actingAs($this->coordinator)
        ->get(route('unavailability-reviews.index', ['teacher_id' => $teacher->id, 'type' => 'ad_hoc_date']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('unavailabilities.data', 1)
            ->where('unavailabilities.data.0.id', $match->id));
});

test('role bundles grant declaring to teachers and reviewing to coordinators', function () {
    expect(UserRole::Teacher->hasPermission(Permission::DeclareUnavailability))->toBeTrue()
        ->and(UserRole::Teacher->hasPermission(Permission::ReviewUnavailability))->toBeFalse()
        ->and(UserRole::Coordinator->hasPermission(Permission::ReviewUnavailability))->toBeTrue()
        ->and(UserRole::Coordinator->hasPermission(Permission::DeclareUnavailability))->toBeFalse()
        ->and(UserRole::Student->hasPermission(Permission::DeclareUnavailability))->toBeFalse()
        ->and(UserRole::Administrator->hasPermission(Permission::ReviewUnavailability))->toBeTrue();
});

test('the pending count is shared only with reviewers', function () {
    TeacherUnavailability::factory()->count(2)->create();
    TeacherUnavailability::factory()->approved()->create();

    $this->actingAs($this->coordinator)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('pendingUnavailabilityCount', 2));

    $this->actingAs(User::factory()->teacher()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('pendingUnavailabilityCount', null));
});
