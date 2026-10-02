<?php

use App\Actions\Modules\CreateModuleAction;
use App\Actions\Modules\UpdateModuleAction;
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

test('module creation rejects total hours of zero or negative', function () {
    $response = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Maths',
        'code' => 'MTH-101',
        'total_hours' => 0,
        'lecture_hours' => 0,
        'tp_hours' => 0,
        'color_code' => '#3B82F6',
    ]);

    $response->assertSessionHasErrors(['total_hours']);
    expect(Module::count())->toBe(0);

    $negativeResponse = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Maths',
        'code' => 'MTH-101',
        'total_hours' => -10,
        'lecture_hours' => 0,
        'tp_hours' => 0,
        'color_code' => '#3B82F6',
    ]);

    $negativeResponse->assertSessionHasErrors(['total_hours']);
    expect(Module::count())->toBe(0);
});

test('module creation rejects syllabus where lecture plus tp hours exceed total hours', function () {
    $response = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Algorithmique',
        'code' => 'ALGO-101',
        'total_hours' => 30,
        'lecture_hours' => 20,
        'tp_hours' => 15, // 20 + 15 = 35 > 30
        'color_code' => '#3B82F6',
    ]);

    $response->assertSessionHasErrors(['lecture_hours']);
    expect(Module::count())->toBe(0);
});

test('single action CreateModuleAction strictly rejects syllabus exceeding total hours', function () {
    $action = new CreateModuleAction;

    expect(fn () => $action->execute([
        'program_id' => $this->program->id,
        'name' => 'Architecture',
        'code' => 'ARCH-101',
        'total_hours' => 40,
        'lecture_hours' => 30,
        'tp_hours' => 20, // 50 > 40
        'color_code' => '#3B82F6',
    ]))->toThrow(InvalidArgumentException::class, 'The sum of lecture hours and TP hours cannot exceed total syllabus hours.');
});

test('single action UpdateModuleAction strictly rejects syllabus exceeding total hours', function () {
    $module = Module::factory()->create([
        'program_id' => $this->program->id,
        'total_hours' => 40,
        'lecture_hours' => 20,
        'tp_hours' => 20,
    ]);

    $action = app(UpdateModuleAction::class);

    expect(fn () => $action->execute($module, [
        'lecture_hours' => 25, // 25 + 20 = 45 > 40
    ]))->toThrow(InvalidArgumentException::class, 'The sum of lecture hours and TP hours cannot exceed total syllabus hours.');
});

test('module creation rejects invalid hex color formats', function () {
    $response = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Web Dev',
        'code' => 'WEB-101',
        'total_hours' => 40,
        'lecture_hours' => 20,
        'tp_hours' => 20,
        'color_code' => 'blue', // not a valid hex string
    ]);

    $response->assertSessionHasErrors(['color_code']);

    $action = new CreateModuleAction;
    expect(fn () => $action->execute([
        'program_id' => $this->program->id,
        'name' => 'Web Dev',
        'code' => 'WEB-101',
        'total_hours' => 40,
        'lecture_hours' => 20,
        'tp_hours' => 20,
        'color_code' => 'invalid-hex',
    ]))->toThrow(InvalidArgumentException::class, 'Invalid hex color code: invalid-hex.');
});

test('module code must be unique within a program but can be reused across different programs', function () {
    Module::factory()->create([
        'program_id' => $this->program->id,
        'code' => 'ISI-101',
    ]);

    // Same program, duplicate code -> rejected
    $duplicateResponse = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'name' => 'Another Module',
        'code' => 'ISI-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#10B981',
    ]);
    $duplicateResponse->assertSessionHasErrors(['code']);

    // Different program, same code -> accepted
    $anotherProgram = Program::factory()->create(['department_id' => $this->department->id]);
    $validResponse = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $anotherProgram->id,
        'name' => 'Same Code In Other Program',
        'code' => 'ISI-101',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#10B981',
    ]);
    $validResponse->assertSessionHasNoErrors();
    expect(Module::where('code', 'ISI-101')->count())->toBe(2);
});

test('module creation succeeds with valid syllabus and hex color', function () {
    $response = $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'teacher_id' => $this->teacher->id,
        'name' => 'Réseaux & Protocoles',
        'code' => 'NET-201',
        'total_hours' => 50,
        'lecture_hours' => 30,
        'tp_hours' => 20,
        'color_code' => '#8B5CF6',
        'description' => 'Protocoles TCP/IP et routage dynamique.',
    ]);

    $response->assertSessionHasNoErrors();
    $module = Module::where('code', 'NET-201')->firstOrFail();
    expect($module->name)->toBe('Réseaux & Protocoles');
    expect($module->total_hours)->toBe(50);
    expect($module->lecture_hours)->toBe(30);
    expect($module->tp_hours)->toBe(20);
    expect($module->teacher_id)->toBe($this->teacher->id);
    expect($module->color_code)->toBe('#8B5CF6');
});

test('module update over http rejects hours exceeding the stored total', function () {
    $module = Module::factory()->create([
        'program_id' => $this->program->id,
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
    ]);

    $response = $this->actingAs($this->coordinator)->put(route('modules.update', $module), [
        'lecture_hours' => 20,
    ]);

    $response->assertSessionHasErrors(['lecture_hours']);
    expect($module->refresh()->lecture_hours)->toBe(15);
});

test('module update rejects moving a module into a program that already has its code', function () {
    $otherProgram = Program::factory()->create(['department_id' => $this->department->id]);
    Module::factory()->create(['program_id' => $otherProgram->id, 'code' => 'MTH-101']);
    $module = Module::factory()->create(['program_id' => $this->program->id, 'code' => 'MTH-101']);

    $response = $this->actingAs($this->coordinator)->put(route('modules.update', $module), [
        'program_id' => $otherProgram->id,
    ]);

    $response->assertSessionHasErrors(['code']);
    expect($module->refresh()->program_id)->toBe($this->program->id);
});

test('modules only accept teachers as the assigned teacher', function (string $role) {
    $notATeacher = User::factory()->{$role}()->create();
    $module = Module::factory()->create(['program_id' => $this->program->id]);

    $this->actingAs($this->coordinator)->post(route('modules.store'), [
        'program_id' => $this->program->id,
        'teacher_id' => $notATeacher->id,
        'name' => 'Maths',
        'code' => 'MTH-102',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#3B82F6',
    ])->assertSessionHasErrors(['teacher_id']);

    $this->actingAs($this->coordinator)->put(route('modules.update', $module), [
        'teacher_id' => $notATeacher->id,
    ])->assertSessionHasErrors(['teacher_id']);

    expect(fn () => app(CreateModuleAction::class)->execute([
        'program_id' => $this->program->id,
        'teacher_id' => $notATeacher->id,
        'name' => 'Maths',
        'code' => 'MTH-103',
        'total_hours' => 30,
        'lecture_hours' => 15,
        'tp_hours' => 15,
        'color_code' => '#3B82F6',
    ]))->toThrow(InvalidArgumentException::class);
})->with(['student', 'coordinator']);
