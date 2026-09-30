<?php

use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->campus = Campus::factory()->create(['name' => 'Campus Casablanca', 'code' => 'CASA']);
    $this->building = Building::factory()->create([
        'campus_id' => $this->campus->id,
        'name' => 'Bâtiment A',
        'code' => 'BAT-A',
    ]);
    $this->coordinator = User::factory()->coordinator()->create();
});

test('coordinators can view room management index with referentials and statistics', function () {
    Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Amphi 1',
        'course_capacity' => 100,
        'exam_capacity' => 50,
        'has_projector' => true,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->coordinator)->get(route('rooms.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/index')
            ->has('rooms', 1)
            ->has('campuses', 1)
            ->where('stats.total_rooms', 1)
            ->where('stats.active_rooms', 1)
            ->where('stats.total_course_capacity', 100)
            ->where('stats.total_exam_capacity', 50)
            ->where('stats.total_campuses', 1)
            ->where('stats.total_buildings', 1)
        );
});

test('coordinators can filter rooms by campus and building', function () {
    $otherCampus = Campus::factory()->create(['name' => 'Campus Rabat', 'code' => 'RABAT']);
    $otherBuilding = Building::factory()->create(['campus_id' => $otherCampus->id, 'name' => 'Bâtiment Central']);

    $roomCasa = Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Salle Casa',
    ]);

    $roomRabat = Room::factory()->create([
        'building_id' => $otherBuilding->id,
        'name' => 'Salle Rabat',
    ]);

    $response = $this->actingAs($this->coordinator)->get(route('rooms.index', [
        'campus_id' => $this->campus->id,
    ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/index')
            ->has('rooms', 1)
            ->where('rooms.0.id', $roomCasa->id)
        );

    $responseBuilding = $this->actingAs($this->coordinator)->get(route('rooms.index', [
        'building_id' => $otherBuilding->id,
    ]));

    $responseBuilding->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/index')
            ->has('rooms', 1)
            ->where('rooms.0.id', $roomRabat->id)
        );
});

test('coordinators can filter rooms by equipment flags and search keyword', function () {
    $labRoom = Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Labo Informatique',
        'is_lab' => true,
        'has_projector' => true,
    ]);

    $lectureRoom = Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Amphi Hassan',
        'is_lab' => false,
        'has_projector' => false,
    ]);

    // Filter by is_lab
    $responseLab = $this->actingAs($this->coordinator)->get(route('rooms.index', [
        'is_lab' => '1',
    ]));

    $responseLab->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rooms', 1)
            ->where('rooms.0.id', $labRoom->id)
        );

    // Search keyword
    $responseSearch = $this->actingAs($this->coordinator)->get(route('rooms.index', [
        'search' => 'Hassan',
    ]));

    $responseSearch->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rooms', 1)
            ->where('rooms.0.id', $lectureRoom->id)
        );
});

test('coordinators can create a new room with equipment flags and dual capacities', function () {
    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Salle 204',
        'code' => 'A-204',
        'floor' => 2,
        'course_capacity' => 45,
        'exam_capacity' => 22,
        'has_projector' => true,
        'is_lab' => true,
        'has_computers' => true,
        'has_sound_system' => false,
    ]);

    $response->assertRedirect();
    $room = Room::where('name', 'Salle 204')->first();
    expect($room)->not->toBeNull();
    expect($room->course_capacity)->toBe(45);
    expect($room->exam_capacity)->toBe(22);
    expect($room->is_lab)->toBeTrue();
    expect($room->has_computers)->toBeTrue();
    expect($room->has_projector)->toBeTrue();
});

test('coordinators can update room attributes and capacities', function () {
    $room = Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Old Name',
        'course_capacity' => 30,
        'exam_capacity' => 15,
        'has_projector' => false,
    ]);

    $response = $this->actingAs($this->coordinator)->put(route('rooms.update', $room), [
        'name' => 'Updated Name',
        'course_capacity' => 60,
        'exam_capacity' => 30,
        'has_projector' => true,
    ]);

    $response->assertRedirect();
    $room->refresh();
    expect($room->name)->toBe('Updated Name');
    expect($room->course_capacity)->toBe(60);
    expect($room->exam_capacity)->toBe(30);
    expect($room->has_projector)->toBeTrue();
});

test('coordinators can toggle room active and deactivated states', function () {
    $room = Room::factory()->create([
        'building_id' => $this->building->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->coordinator)->patch(route('rooms.toggle-active', $room));

    $response->assertRedirect();
    expect($room->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->coordinator)->patch(route('rooms.toggle-active', $room));
    expect($room->refresh()->is_active)->toBeTrue();
});

test('coordinators can create and update campus and building referentials', function () {
    // Create Campus
    $campusResponse = $this->actingAs($this->coordinator)->post(route('campuses.store'), [
        'name' => 'Campus Fes',
        'code' => 'FES',
        'city' => 'Fes',
    ]);
    $campusResponse->assertRedirect();
    $fesCampus = Campus::where('code', 'FES')->first();
    expect($fesCampus)->not->toBeNull();

    // Create Building
    $buildingResponse = $this->actingAs($this->coordinator)->post(route('buildings.store'), [
        'campus_id' => $fesCampus->id,
        'name' => 'Bâtiment Sciences',
        'code' => 'SCI',
    ]);
    $buildingResponse->assertRedirect();
    expect(Building::where('name', 'Bâtiment Sciences')->exists())->toBeTrue();
});
