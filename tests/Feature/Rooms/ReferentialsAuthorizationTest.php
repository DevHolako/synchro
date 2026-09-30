<?php

use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->campus = Campus::factory()->create();
    $this->building = Building::factory()->create(['campus_id' => $this->campus->id]);
    $this->room = Room::factory()->create(['building_id' => $this->building->id]);

    $this->admin = User::factory()->admin()->create();
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create();
    $this->student = User::factory()->student()->create();
});

test('administrator and coordinator can create and mutate referential records', function () {
    // Admin create room
    $adminResponse = $this->actingAs($this->admin)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Amphi Admin',
        'course_capacity' => 80,
        'exam_capacity' => 40,
    ]);
    $adminResponse->assertRedirect();
    expect(Room::where('name', 'Amphi Admin')->exists())->toBeTrue();

    // Coordinator create room
    $coordResponse = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Amphi Coord',
        'course_capacity' => 60,
        'exam_capacity' => 30,
    ]);
    $coordResponse->assertRedirect();
    expect(Room::where('name', 'Amphi Coord')->exists())->toBeTrue();
});

test('teachers are strictly forbidden from mutating referential data', function () {
    // Teacher attempt to create room -> 403
    $this->actingAs($this->teacher)
        ->post(route('rooms.store'), [
            'building_id' => $this->building->id,
            'name' => 'Illegal Room',
            'course_capacity' => 50,
            'exam_capacity' => 25,
        ])
        ->assertForbidden();

    // Teacher attempt to update room -> 403
    $this->actingAs($this->teacher)
        ->put(route('rooms.update', $this->room), [
            'name' => 'Hacked Name',
        ])
        ->assertForbidden();

    // Teacher attempt to toggle room -> 403
    $this->actingAs($this->teacher)
        ->patch(route('rooms.toggle-active', $this->room))
        ->assertForbidden();

    // Teacher attempt to create campus -> 403
    $this->actingAs($this->teacher)
        ->post(route('campuses.store'), [
            'name' => 'Illegal Campus',
            'code' => 'ILL',
        ])
        ->assertForbidden();

    // Teacher attempt to create building -> 403
    $this->actingAs($this->teacher)
        ->post(route('buildings.store'), [
            'campus_id' => $this->campus->id,
            'name' => 'Illegal Building',
        ])
        ->assertForbidden();
});

test('students are strictly forbidden from mutating referential data', function () {
    // Student attempt to create room -> 403
    $this->actingAs($this->student)
        ->post(route('rooms.store'), [
            'building_id' => $this->building->id,
            'name' => 'Student Room',
            'course_capacity' => 50,
            'exam_capacity' => 25,
        ])
        ->assertForbidden();

    // Student attempt to update room -> 403
    $this->actingAs($this->student)
        ->put(route('rooms.update', $this->room), [
            'name' => 'Student Mod',
        ])
        ->assertForbidden();

    // Student attempt to toggle room -> 403
    $this->actingAs($this->student)
        ->patch(route('rooms.toggle-active', $this->room))
        ->assertForbidden();
});

test('unauthenticated guests are redirected to login', function () {
    $this->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Guest Room',
        'course_capacity' => 50,
        'exam_capacity' => 25,
    ])->assertRedirect(route('login'));

    $this->get(route('rooms.index'))->assertRedirect(route('login'));
});
