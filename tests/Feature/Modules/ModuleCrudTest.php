<?php

use App\Models\Department;
use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->teacher = User::factory()->teacher()->create();
});

test('coordinator can update module attributes and assign teacher', function () {
    $module = Module::factory()->create([
        'program_id' => $this->program->id,
        'teacher_id' => null,
        'name' => 'Old Module Name',
        'code' => 'OLD-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
    ]);

    $newTeacher = User::factory()->teacher()->create();

    $response = $this->actingAs($this->coordinator)->put(route('modules.update', $module), [
        'name' => 'Updated Module Name',
        'code' => 'NEW-101',
        'teacher_id' => $newTeacher->id,
        'total_hours' => 45,
        'lecture_hours' => 30,
        'tp_hours' => 15,
        'color_code' => '#EC4899',
    ]);

    $response->assertSessionHasNoErrors();
    $module->refresh();

    expect($module->name)->toBe('Updated Module Name');
    expect($module->code)->toBe('NEW-101');
    expect($module->teacher_id)->toBe($newTeacher->id);
    expect($module->total_hours)->toBe(45);
    expect($module->lecture_hours)->toBe(30);
    expect($module->tp_hours)->toBe(15);
    expect($module->color_code)->toBe('#EC4899');
});

test('coordinator can toggle active status of module', function () {
    $module = Module::factory()->create(['program_id' => $this->program->id, 'is_active' => true]);

    $this->actingAs($this->coordinator)->patch(route('modules.toggle-active', $module));
    expect($module->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->coordinator)->patch(route('modules.toggle-active', $module));
    expect($module->refresh()->is_active)->toBeTrue();
});

test('deleting a program cascades and deletes its modules', function () {
    $module1 = Module::factory()->create(['program_id' => $this->program->id]);
    $module2 = Module::factory()->create(['program_id' => $this->program->id]);

    expect(Module::count())->toBe(2);

    $this->program->delete();

    expect(Module::count())->toBe(0);
});

test('deleting an assigned teacher sets teacher_id to null on module without deleting the module', function () {
    $module = Module::factory()->create([
        'program_id' => $this->program->id,
        'teacher_id' => $this->teacher->id,
    ]);

    expect($module->teacher_id)->toBe($this->teacher->id);

    $this->teacher->delete();

    $module->refresh();
    expect($module->teacher_id)->toBeNull();
    expect(Module::count())->toBe(1);
});

test('scopes active and forProgram filter modules properly', function () {
    $active = Module::factory()->create(['program_id' => $this->program->id, 'is_active' => true]);
    $inactive = Module::factory()->inactive()->create(['program_id' => $this->program->id]);

    $anotherProgram = Program::factory()->create(['department_id' => $this->department->id]);
    $otherModule = Module::factory()->create(['program_id' => $anotherProgram->id, 'is_active' => true]);

    expect(Module::active()->count())->toBe(2);
    expect(Module::forProgram($this->program->id)->count())->toBe(2);
    expect(Module::active()->forProgram($this->program->id)->count())->toBe(1);
    expect(Module::active()->forProgram($this->program->id)->first()->id)->toBe($active->id);
});
