<?php

use App\Models\Department;
use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create();
    $this->student = User::factory()->student()->create();

    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->module = Module::factory()->create(['program_id' => $this->program->id]);
});

test('administrator and coordinator can access index and mutate modules', function () {
    $this->withoutVite();

    // Administrator
    $this->actingAs($this->admin)->get(route('modules.index'))->assertOk();

    $adminResp = $this->actingAs($this->admin)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Admin Module',
        'code' => 'ADM-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#3B82F6',
    ]);
    $adminResp->assertSessionHasNoErrors();
    expect(Module::where('code', 'ADM-101')->exists())->toBeTrue();

    // Coordinator
    $this->actingAs($this->coordinator)->get(route('modules.index'))->assertOk();

    $coordResp = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Coord Module',
        'code' => 'CRD-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#10B981',
    ]);
    $coordResp->assertSessionHasNoErrors();
    expect(Module::where('code', 'CRD-101')->exists())->toBeTrue();
});

test('teacher can view modules index but cannot create, update, or toggle modules', function () {
    $this->withoutVite();

    // Teachers can view modules catalog (syllabus awareness)
    $this->actingAs($this->teacher)->get(route('modules.index'))->assertOk();

    // Teachers cannot create
    $this->actingAs($this->teacher)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Teacher Created Module',
        'code' => 'TCH-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#3B82F6',
    ])->assertForbidden();

    // Teachers cannot update
    $this->actingAs($this->teacher)->put(route('modules.update', $this->module), [
        'name' => 'Teacher Altered Name',
    ])->assertForbidden();

    // Teachers cannot toggle active status
    $this->actingAs($this->teacher)->patch(route('modules.toggle-active', $this->module))
        ->assertForbidden();
});

test('students are rejected with 403 on index and mutation endpoints', function () {
    $this->actingAs($this->student)->get(route('modules.index'))->assertForbidden();

    $this->actingAs($this->student)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Student Module',
        'code' => 'STU-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#3B82F6',
    ])->assertForbidden();

    $this->actingAs($this->student)->put(route('modules.update', $this->module), [
        'name' => 'Student Modified',
    ])->assertForbidden();

    $this->actingAs($this->student)->patch(route('modules.toggle-active', $this->module))
        ->assertForbidden();
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('modules.index'))->assertRedirect(route('login'));
    $this->post(route('modules.store'), [])->assertRedirect(route('login'));
    $this->put(route('modules.update', $this->module), [])->assertRedirect(route('login'));
    $this->patch(route('modules.toggle-active', $this->module))->assertRedirect(route('login'));
});
