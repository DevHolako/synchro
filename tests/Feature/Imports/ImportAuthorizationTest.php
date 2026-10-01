<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function emptyRoomsCsv(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('rooms.csv', "campus_code,building,name,course_capacity,exam_capacity\n");
}

test('guests are redirected away from imports', function () {
    $this->get(route('imports.index'))->assertRedirect(route('login'));
    $this->post(route('imports.store', 'rooms'), ['file' => emptyRoomsCsv()])->assertRedirect(route('login'));
});

test('teachers and students cannot access imports', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get(route('imports.index'))->assertForbidden();
    $this->actingAs($user)->get(route('imports.template', 'rooms'))->assertForbidden();
    $this->actingAs($user)->post(route('imports.store', 'rooms'), ['file' => emptyRoomsCsv()])->assertForbidden();
})->with(['teacher', 'student']);

test('administrators see every import type', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('imports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('imports/index')
            ->has('types', 4)
            ->where('types.0.type', 'rooms')
            ->where('types.1.type', 'modules')
            ->where('types.2.type', 'teachers')
            ->where('types.3.type', 'students'));
});

test('coordinators may import referentials but not user accounts', function () {
    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($coordinator)
        ->get(route('imports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('types', 2)
            ->where('types.0.type', 'rooms')
            ->where('types.1.type', 'modules'));

    $this->actingAs($coordinator)->get(route('imports.template', 'teachers'))->assertForbidden();
    $this->actingAs($coordinator)->post(route('imports.store', 'students'), [
        'file' => UploadedFile::fake()->createWithContent('students.csv', "name,email,group_code\nA,a@example.com,G1\n"),
    ])->assertForbidden();

    expect(User::where('email', 'a@example.com')->exists())->toBeFalse();
});

test('templates download the expected header row', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('imports.template', 'rooms'))
        ->assertOk()
        ->assertDownload('synchro-rooms-template.csv');

    expect($response->streamedContent())
        ->toContain('campus_code,building,name,code,floor,course_capacity,exam_capacity');
});

test('unknown import types return not found', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('imports.template', 'grades'))
        ->assertNotFound();
});

test('only csv and xlsx files are accepted', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('imports.store', 'rooms'), [
            'file' => UploadedFile::fake()->create('rooms.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('file');

    expect(Room::count())->toBe(0);
});
