<?php

use App\Actions\Programs\CreateProgramAction;
use App\Actions\StudentGroups\CreateStudentGroupAction;
use App\Actions\StudentGroups\UpdateStudentGroupAction;
use App\Enums\ProgramModality;
use App\Models\Department;
use App\Models\Program;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create([
        'department_id' => $this->department->id,
        'program_modality' => ProgramModality::FormationInitiale,
    ]);
});

test('student group creation rejects headcount of zero or negative', function () {
    $response = $this->actingAs($this->coordinator)->post(route('student-groups.store'), [
        'program_id' => $this->program->id,
        'name' => 'Groupe Test',
        'academic_year' => '2026-2027',
        'expected_headcount' => 0,
    ]);

    $response->assertSessionHasErrors(['expected_headcount']);
    expect(StudentGroup::count())->toBe(0);

    $negativeResponse = $this->actingAs($this->coordinator)->post(route('student-groups.store'), [
        'program_id' => $this->program->id,
        'name' => 'Groupe Test 2',
        'academic_year' => '2026-2027',
        'expected_headcount' => -5,
    ]);

    $negativeResponse->assertSessionHasErrors(['expected_headcount']);
    expect(StudentGroup::count())->toBe(0);
});

test('single action CreateStudentGroupAction strictly rejects non-positive headcount', function () {
    $action = new CreateStudentGroupAction;

    expect(fn () => $action->execute([
        'program_id' => $this->program->id,
        'name' => 'Action Test',
        'academic_year' => '2026-2027',
        'expected_headcount' => 0,
    ]))->toThrow(InvalidArgumentException::class, 'Expected headcount must be greater than 0.');

    expect(fn () => $action->execute([
        'program_id' => $this->program->id,
        'name' => 'Action Test 2',
        'academic_year' => '2026-2027',
        'expected_headcount' => -10,
    ]))->toThrow(InvalidArgumentException::class, 'Expected headcount must be greater than 0.');
});

test('single action UpdateStudentGroupAction strictly rejects non-positive headcount', function () {
    $group = StudentGroup::factory()->create([
        'program_id' => $this->program->id,
        'expected_headcount' => 25,
    ]);

    $action = new UpdateStudentGroupAction;

    expect(fn () => $action->execute($group, [
        'expected_headcount' => 0,
    ]))->toThrow(InvalidArgumentException::class, 'Expected headcount must be greater than 0.');
});

test('program creation requires valid program_modality', function () {
    $response = $this->actingAs($this->coordinator)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => 'Master Big Data',
        'code' => 'M-BD',
        'program_modality' => 'invalid_modality',
    ]);

    $response->assertSessionHasErrors(['program_modality']);
    expect(Program::count())->toBe(1); // Only the one created in beforeEach
});

test('single action CreateProgramAction strictly rejects invalid modality', function () {
    $action = new CreateProgramAction;

    expect(fn () => $action->execute([
        'department_id' => $this->department->id,
        'name' => 'Master Big Data',
        'code' => 'M-BD',
        'program_modality' => 'invalid_modality',
    ]))->toThrow(InvalidArgumentException::class, 'Invalid program modality: invalid_modality.');
});

test('program creation succeeds with formation_initiale and temps_amenage modalities', function () {
    $responseInit = $this->actingAs($this->coordinator)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => '1CI Initial',
        'code' => '1CI-INIT',
        'program_modality' => 'formation_initiale',
    ]);

    $responseInit->assertSessionHasNoErrors();
    $initProg = Program::where('code', '1CI-INIT')->firstOrFail();
    expect($initProg->program_modality)->toBe(ProgramModality::FormationInitiale);

    $responseTa = $this->actingAs($this->coordinator)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => 'Master TA',
        'code' => 'M-TA',
        'program_modality' => 'temps_amenage',
    ]);

    $responseTa->assertSessionHasNoErrors();
    $taProg = Program::where('code', 'M-TA')->firstOrFail();
    expect($taProg->program_modality)->toBe(ProgramModality::TempsAmenage);
});

test('unique constraints on department code, program code within department, and group name within academic year', function () {
    Department::factory()->create(['code' => 'ISI']);

    $responseDept = $this->actingAs($this->coordinator)->post(route('departments.store'), [
        'name' => 'Another Dept',
        'code' => 'ISI',
    ]);
    $responseDept->assertSessionHasErrors(['code']);

    // Program code unique within department
    $responseProg = $this->actingAs($this->coordinator)->post(route('programs.store'), [
        'department_id' => $this->department->id,
        'name' => 'Duplicate Code Program',
        'code' => $this->program->code,
        'program_modality' => 'formation_initiale',
    ]);
    $responseProg->assertSessionHasErrors(['code']);

    // Student group unique within program and academic year
    StudentGroup::factory()->create([
        'program_id' => $this->program->id,
        'name' => 'Groupe 1',
        'academic_year' => '2026-2027',
        'expected_headcount' => 30,
    ]);

    $responseGroup = $this->actingAs($this->coordinator)->post(route('student-groups.store'), [
        'program_id' => $this->program->id,
        'name' => 'Groupe 1',
        'academic_year' => '2026-2027',
        'expected_headcount' => 35,
    ]);
    $responseGroup->assertSessionHasErrors(['name']);
});

test('academic structure index endpoint filters by program_modality correctly', function () {
    $this->withoutVite();

    $progInitial = Program::factory()->formationInitiale()->create([
        'department_id' => $this->department->id,
        'name' => 'Initial Program',
    ]);

    $progTa = Program::factory()->tempsAmenage()->create([
        'department_id' => $this->department->id,
        'name' => 'Executive TA Program',
    ]);

    // Filter temps_amenage
    $response = $this->actingAs($this->coordinator)->get(route('academic-structure.index', [
        'program_modality' => 'temps_amenage',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('academic-structure/index')
        ->has('programs', 1)
        ->where('programs.0.id', $progTa->id)
    );

    // Filter formation_initiale
    $responseInit = $this->actingAs($this->coordinator)->get(route('academic-structure.index', [
        'program_modality' => 'formation_initiale',
    ]));

    $responseInit->assertOk();
    $responseInit->assertInertia(fn (Assert $page) => $page
        ->component('academic-structure/index')
        ->has('programs', 2) // $this->program + $progInitial
    );
});
