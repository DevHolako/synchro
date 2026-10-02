<?php

use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('student can visit dashboard and receives student data', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats')
            ->has('upcomingSessions')
            ->has('upcomingExams')
            ->has('recentNotifications')
            ->where('permissions.canViewSchedules', true)
            ->where('permissions.canManageSchedules', false)
            ->where('permissions.canViewExams', true)
            ->where('permissions.canManageExams', false)
            ->where('permissions.canManageReferentials', false)
        );
});

test('teacher can visit dashboard and receives teacher permissions', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats')
            ->where('permissions.canViewSchedules', true)
            ->where('permissions.canDeclareUnavailability', true)
            ->where('permissions.canReviewUnavailability', false)
            ->where('permissions.canEnterGrades', true)
        );
});

test('coordinator can visit dashboard and receives institutional statistics', function () {
    Room::factory()->count(2)->create(['is_active' => true]);
    StudentGroup::factory()->count(2)->create(['is_active' => true]);

    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($coordinator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats.active_rooms')
            ->has('stats.active_groups')
            ->where('permissions.canManageSchedules', true)
            ->where('permissions.canManageReferentials', true)
            ->where('permissions.canReviewUnavailability', true)
        );
});
