<?php

use App\Actions\Rooms\CreateRoomAction;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->campus = Campus::factory()->create();
    $this->building = Building::factory()->create(['campus_id' => $this->campus->id]);
    $this->coordinator = User::factory()->coordinator()->create();
});

test('room creation rejects course capacity of zero or negative', function () {
    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Salle 101',
        'course_capacity' => 0,
        'exam_capacity' => 0,
    ]);

    $response->assertSessionHasErrors(['course_capacity']);
});

test('room creation rejects exam capacity of zero or negative', function () {
    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Salle 101',
        'course_capacity' => 40,
        'exam_capacity' => 0,
    ]);

    $response->assertSessionHasErrors(['exam_capacity']);
});

test('room creation strictly rejects exam capacity exceeding course capacity', function () {
    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Salle 101',
        'course_capacity' => 30,
        'exam_capacity' => 35,
    ]);

    $response->assertSessionHasErrors(['exam_capacity']);
    expect(Room::count())->toBe(0);
});

test('room creation accepts exam capacity equal to course capacity', function () {
    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Amphi 1',
        'course_capacity' => 50,
        'exam_capacity' => 50,
        'has_projector' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $room = Room::first();
    expect($room)->not->toBeNull();
    expect($room->course_capacity)->toBe(50);
    expect($room->exam_capacity)->toBe(50);
});

test('action level rejects invalid capacity constraints with InvalidArgumentException', function () {
    $action = app(CreateRoomAction::class);

    expect(fn () => $action->execute([
        'building_id' => $this->building->id,
        'name' => 'Invalid Room',
        'course_capacity' => 20,
        'exam_capacity' => 25,
    ]))->toThrow(InvalidArgumentException::class, 'Exam capacity cannot exceed course capacity.');

    expect(fn () => $action->execute([
        'building_id' => $this->building->id,
        'name' => 'Invalid Zero',
        'course_capacity' => 0,
        'exam_capacity' => 0,
    ]))->toThrow(InvalidArgumentException::class, 'Course capacity must be greater than 0.');
});

test('room name must be unique within the same building', function () {
    Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Salle 101',
    ]);

    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $this->building->id,
        'name' => 'Salle 101',
        'course_capacity' => 40,
        'exam_capacity' => 20,
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('identical room name is allowed in different buildings', function () {
    $otherBuilding = Building::factory()->create(['campus_id' => $this->campus->id]);

    Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Salle 101',
    ]);

    $response = $this->actingAs($this->coordinator)->post(route('rooms.store'), [
        'building_id' => $otherBuilding->id,
        'name' => 'Salle 101',
        'course_capacity' => 40,
        'exam_capacity' => 20,
    ]);

    $response->assertSessionHasNoErrors();
    expect(Room::where('name', 'Salle 101')->count())->toBe(2);
});
