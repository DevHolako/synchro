<?php

use App\Models\Department;
use App\Models\Program;
use App\Models\StudentGroup;
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
    $this->studentGroup = StudentGroup::factory()->create(['program_id' => $this->program->id]);
});

test('administrator and coordinator with permissions can create academic entities', function () {
    // Administrator
    $respAdminDept = $this->actingAs($this->admin)->post(route('departments.store'), [
        'name' => 'Department Created By Admin',
        'code' => 'ADMIN-DEP',
    ]);
    $respAdminDept->assertSessionHasNoErrors();
    expect(Department::where('code', 'ADMIN-DEP')->exists())->toBeTrue();

    // Coordinator
    $respCoordProg = $this->actingAs($this->coordinator)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => 'Program Created By Coord',
        'code' => 'COORD-PRG',
        'program_modality' => 'formation_initiale',
    ]);
    $respCoordProg->assertSessionHasNoErrors();
    expect(Program::where('code', 'COORD-PRG')->exists())->toBeTrue();

    $respCoordGroup = $this->actingAs($this->coordinator)->post(route('student-groups.store'), [
        'program_id' => $this->program->id,
        'name' => 'Group Created By Coord',
        'academic_year' => '2026-2027',
        'expected_headcount' => 28,
    ]);
    $respCoordGroup->assertSessionHasNoErrors();
    expect(StudentGroup::where('name', 'Group Created By Coord')->exists())->toBeTrue();
});

test('administrator and coordinator can toggle active status of academic entities', function () {
    expect($this->department->is_active)->toBeTrue();

    $this->actingAs($this->coordinator)->patch(route('departments.toggle-active', $this->department));
    expect($this->department->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->patch(route('programs.toggle-active', $this->program));
    expect($this->program->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->coordinator)->patch(route('student-groups.toggle-active', $this->studentGroup));
    expect($this->studentGroup->refresh()->is_active)->toBeFalse();
});

test('teachers without mutation permissions are rejected with 403 on store, update, and toggle', function () {
    // Store department
    $respDept = $this->actingAs($this->teacher)->post(route('departments.store'), [
        'name' => 'Unauthorized Dept',
        'code' => 'UNAUTH',
    ]);
    $respDept->assertForbidden();

    // Store program
    $respProg = $this->actingAs($this->teacher)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => 'Unauthorized Program',
        'code' => 'UNAUTH-P',
        'program_modality' => 'formation_initiale',
    ]);
    $respProg->assertForbidden();

    // Store student group
    $respGroup = $this->actingAs($this->teacher)->post(route('student-groups.store'), [
        'program_id' => $this->program->id,
        'name' => 'Unauthorized Group',
        'academic_year' => '2026-2027',
        'expected_headcount' => 30,
    ]);
    $respGroup->assertForbidden();

    // Toggle active
    $this->actingAs($this->teacher)->patch(route('departments.toggle-active', $this->department))
        ->assertForbidden();
    $this->actingAs($this->teacher)->patch(route('programs.toggle-active', $this->program))
        ->assertForbidden();
    $this->actingAs($this->teacher)->patch(route('student-groups.toggle-active', $this->studentGroup))
        ->assertForbidden();
});

test('students are rejected with 403 on index and mutation endpoints', function () {
    $this->actingAs($this->student)->get(route('academic-structure.index'))
        ->assertForbidden();

    $this->actingAs($this->student)->post(route('departments.store'), [
        'name' => 'Student Dept',
        'code' => 'STU',
    ])->assertForbidden();

    $this->actingAs($this->student)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => 'Student Program',
        'code' => 'STU-P',
        'program_modality' => 'formation_initiale',
    ])->assertForbidden();

    $this->actingAs($this->student)->post(route('student-groups.store'), [
        'program_id' => $this->program->id,
        'name' => 'Student Group',
        'academic_year' => '2026-2027',
        'expected_headcount' => 20,
    ])->assertForbidden();
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('academic-structure.index'))->assertRedirect(route('login'));
    $this->post(route('departments.store'), [])->assertRedirect(route('login'));
    $this->post(route('programs.store'), [])->assertRedirect(route('login'));
    $this->post(route('student-groups.store'), [])->assertRedirect(route('login'));
});
