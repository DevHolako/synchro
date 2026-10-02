<?php

use App\Actions\CourseSessions\CalculateSyllabusProgressAction;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\StudentGroup;

beforeEach(function () {
    $this->program = Program::factory()->create();
    $this->group = StudentGroup::factory()->create(['program_id' => $this->program->id]);
    $this->module = Module::factory()->create(['program_id' => $this->program->id, 'code' => 'MOD-A', 'total_hours' => 30]);
});

/**
 * @return array<string, int>
 */
function plannedMinutesByCode(StudentGroup $group): array
{
    return collect(app(CalculateSyllabusProgressAction::class)->execute($group->id))
        ->pluck('planned_minutes', 'code')
        ->all();
}

test('planned hours add up every session of the group, past and future, to the quarter hour', function () {
    $this->travelTo('2026-10-07 09:00');
    CourseSession::factory()->between('2026-09-01 08:00', '2026-09-01 09:45')->forGroups($this->group)->create(['module_id' => $this->module->id]);
    CourseSession::factory()->between('2026-11-02 14:00', '2026-11-02 16:00')->forGroups($this->group)->create(['module_id' => $this->module->id]);

    expect(app(CalculateSyllabusProgressAction::class)->execute($this->group->id))->toBe([[
        'module_id' => $this->module->id,
        'code' => 'MOD-A',
        'name' => $this->module->name,
        'color_code' => $this->module->color_code,
        'total_hours' => 30,
        'planned_minutes' => 225,
    ]]);
});

test('a session shared by several groups counts in full for each of them', function () {
    $other = StudentGroup::factory()->create(['program_id' => $this->program->id]);
    CourseSession::factory()->between('2026-10-05 08:00', '2026-10-05 11:00')->forGroups($this->group, $other)->create(['module_id' => $this->module->id]);
    CourseSession::factory()->between('2026-10-06 08:00', '2026-10-06 10:00')->forGroups($other)->create(['module_id' => $this->module->id]);

    expect(plannedMinutesByCode($this->group))->toBe(['MOD-A' => 180])
        ->and(plannedMinutesByCode($other))->toBe(['MOD-A' => 300]);
});

test('modules without sessions show zero, inactive ones only once they have sessions', function () {
    Module::factory()->create(['program_id' => $this->program->id, 'code' => 'MOD-B']);
    Module::factory()->inactive()->create(['program_id' => $this->program->id, 'code' => 'MOD-C']);
    $retired = Module::factory()->inactive()->create(['program_id' => $this->program->id, 'code' => 'MOD-D']);
    CourseSession::factory()->between('2026-10-05 08:00', '2026-10-05 10:00')->forGroups($this->group)->create(['module_id' => $retired->id]);
    Module::factory()->create(['code' => 'MOD-OTHER-PROGRAM']);

    expect(plannedMinutesByCode($this->group))->toBe(['MOD-A' => 0, 'MOD-B' => 0, 'MOD-D' => 120]);
});
