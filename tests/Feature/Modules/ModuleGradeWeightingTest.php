<?php

use App\Actions\Modules\CreateModuleAction;
use App\Actions\Modules\UpdateModuleAction;
use App\Models\Department;
use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coordinator = User::factory()->coordinator()->create();
    $this->program = Program::factory()->for(Department::factory())->create();
});

/**
 * @return array<string, mixed>
 */
function weightedModulePayload(int $programId, array $overrides = []): array
{
    return [
        'program_id' => $programId,
        'name' => fake()->words(2, true),
        'code' => fake()->unique()->bothify('MOD-###'),
        'total_hours' => 40,
        'lecture_hours' => 24,
        'tp_hours' => 16,
        'color_code' => '#3B82F6',
        ...$overrides,
    ];
}

test('a module created without a weighting is graded 100% on the final exam', function () {
    $this->actingAs($this->coordinator)
        ->post(route('modules.store'), weightedModulePayload($this->program->id))
        ->assertSessionHasNoErrors();

    $module = Module::sole();

    expect($module->continuous_assessment_weight)->toBe(0)
        ->and($module->exam_weight)->toBe(100);
});

test('a coordinator sets the continuous assessment share and the exam weighs the rest', function () {
    $this->actingAs($this->coordinator)
        ->post(route('modules.store'), weightedModulePayload($this->program->id, ['continuous_assessment_weight' => 40]))
        ->assertSessionHasNoErrors();

    $module = Module::sole();

    expect($module->continuous_assessment_weight)->toBe(40)
        ->and($module->exam_weight)->toBe(60);

    $this->actingAs($this->coordinator)
        ->put(route('modules.update', $module), ['continuous_assessment_weight' => 25])
        ->assertSessionHasNoErrors();

    expect($module->fresh()->exam_weight)->toBe(75);
});

test('weightings that leave the final exam without weight or are not whole percentages are rejected', function (mixed $weight) {
    $this->actingAs($this->coordinator)
        ->post(route('modules.store'), weightedModulePayload($this->program->id, ['continuous_assessment_weight' => $weight]))
        ->assertSessionHasErrors('continuous_assessment_weight');

    $module = Module::factory()->for($this->program)->create(['continuous_assessment_weight' => 30]);

    $this->actingAs($this->coordinator)
        ->put(route('modules.update', $module), ['continuous_assessment_weight' => $weight])
        ->assertSessionHasErrors('continuous_assessment_weight');

    expect(Module::count())->toBe(1)
        ->and($module->fresh()->continuous_assessment_weight)->toBe(30);
})->with([
    'the whole grade on continuous assessment' => 100,
    'more than the whole grade' => 120,
    'a negative share' => -10,
    'a fraction' => 33.5,
]);

test('the out-of-range message is translated', function () {
    app()->setLocale('fr');

    $this->actingAs($this->coordinator)
        ->post(route('modules.store'), weightedModulePayload($this->program->id, ['continuous_assessment_weight' => 100]))
        ->assertSessionHasErrors([
            'continuous_assessment_weight' => __('messages.module_continuous_assessment_weight_range', ['max' => 99]),
        ]);
});

test('a teacher cannot change a module weighting', function () {
    $module = Module::factory()->for($this->program)->create();

    $this->actingAs(User::factory()->teacher()->create())
        ->put(route('modules.update', $module), ['continuous_assessment_weight' => 40])
        ->assertForbidden();

    expect($module->fresh()->continuous_assessment_weight)->toBe(0);
});

test('the actions refuse a weighting out of range', function () {
    $module = Module::factory()->for($this->program)->create();

    expect(fn () => app(CreateModuleAction::class)->execute(
        weightedModulePayload($this->program->id, ['continuous_assessment_weight' => 100]),
    ))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(UpdateModuleAction::class)->execute($module, ['continuous_assessment_weight' => -1]))
        ->toThrow(InvalidArgumentException::class);
});

test('the modules page sends both weights and the weighting limit', function () {
    Module::factory()->for($this->program)->create(['continuous_assessment_weight' => 40]);

    $this->actingAs($this->coordinator)
        ->get(route('modules.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('modules.0.continuous_assessment_weight', 40)
            ->where('modules.0.exam_weight', 60)
            ->where('limits.max_continuous_assessment_weight', Module::MAX_CONTINUOUS_ASSESSMENT_WEIGHT));
});
