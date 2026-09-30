<?php

use App\Models\Campus;
use App\Models\Department;
use App\Models\Program;
use App\Models\StudentGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('deleting a department cascades and deletes its programs and nested student groups', function () {
    $department = Department::factory()->create();

    $program1 = Program::factory()->create(['department_id' => $department->id]);
    $program2 = Program::factory()->create(['department_id' => $department->id]);

    $group1 = StudentGroup::factory()->create(['program_id' => $program1->id]);
    $group2 = StudentGroup::factory()->create(['program_id' => $program1->id]);
    $group3 = StudentGroup::factory()->create(['program_id' => $program2->id]);

    expect(Department::count())->toBe(1);
    expect(Program::count())->toBe(2);
    expect(StudentGroup::count())->toBe(3);

    $department->delete();

    expect(Department::count())->toBe(0);
    expect(Program::count())->toBe(0);
    expect(StudentGroup::count())->toBe(0);
});

test('deleting a program cascades and deletes its student groups while preserving the department', function () {
    $department = Department::factory()->create();

    $program1 = Program::factory()->create(['department_id' => $department->id]);
    $program2 = Program::factory()->create(['department_id' => $department->id]);

    $group1 = StudentGroup::factory()->create(['program_id' => $program1->id]);
    $group2 = StudentGroup::factory()->create(['program_id' => $program2->id]);

    $program1->delete();

    expect(Department::count())->toBe(1);
    expect(Program::count())->toBe(1);
    expect(Program::first()->id)->toBe($program2->id);

    expect(StudentGroup::count())->toBe(1);
    expect(StudentGroup::first()->id)->toBe($group2->id);
});

test('deleting a campus sets campus_id to null on student groups without deleting them', function () {
    $campus = Campus::factory()->create();
    $department = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $department->id]);

    $group = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'campus_id' => $campus->id,
    ]);

    expect($group->campus_id)->toBe($campus->id);

    $campus->delete();

    $group->refresh();
    expect($group->campus_id)->toBeNull();
    expect(StudentGroup::count())->toBe(1);
});

test('department, program, and student group active scopes filter properly', function () {
    $activeDept = Department::factory()->create(['is_active' => true]);
    $inactiveDept = Department::factory()->inactive()->create();

    expect(Department::active()->count())->toBe(1);
    expect(Department::active()->first()->id)->toBe($activeDept->id);

    $activeProg = Program::factory()->create(['department_id' => $activeDept->id, 'is_active' => true]);
    $inactiveProg = Program::factory()->inactive()->create(['department_id' => $activeDept->id]);

    expect(Program::active()->count())->toBe(1);
    expect(Program::active()->first()->id)->toBe($activeProg->id);

    $activeGroup = StudentGroup::factory()->create(['program_id' => $activeProg->id, 'is_active' => true]);
    $inactiveGroup = StudentGroup::factory()->inactive()->create(['program_id' => $activeProg->id]);

    expect(StudentGroup::active()->count())->toBe(1);
    expect(StudentGroup::active()->first()->id)->toBe($activeGroup->id);
});
