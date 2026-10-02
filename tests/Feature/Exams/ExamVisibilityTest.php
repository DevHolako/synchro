<?php

use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);

    $program = Program::factory()->create();
    $this->teacher = User::factory()->teacher()->create();
    $this->module = Module::factory()->create(['program_id' => $program->id, 'teacher_id' => $this->teacher->id]);
    $this->otherModule = Module::factory()->create(['program_id' => $program->id]);
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->otherGroup = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->student = StudentProfile::factory()->create(['student_group_id' => $this->group->id])->user;
    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();

    $exam = fn (ExamState $state, Module $module, StudentGroup $group) => Exam::factory()->forGroups($group)->create([
        'exam_period_id' => $this->period->id,
        'module_id' => $module->id,
        'state' => $state,
    ]);

    $this->exams = [
        'draft' => $exam(ExamState::Draft, $this->module, $this->group),
        'scheduled' => $exam(ExamState::Scheduled, $this->module, $this->group),
        'published' => $exam(ExamState::Published, $this->otherModule, $this->group),
        'completed' => $exam(ExamState::Completed, $this->module, $this->otherGroup),
        'archived' => $exam(ExamState::Archived, $this->otherModule, $this->otherGroup),
    ];
});

/**
 * @return Closure(array<int, array<string, mixed>>): bool
 */
function examIds(Exam ...$expected): Closure
{
    return fn ($exams) => collect($exams)->pluck('id')->sort()->values()->all() === collect($expected)->pluck('id')->sort()->values()->all();
}

test('exam managers see every state, with counts and form options', function () {
    $this->actingAs(User::factory()->coordinator()->create())
        ->get(route('exams.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exams/index')
            ->where('periodId', $this->period->id)
            ->where('exams', examIds(...array_values($this->exams)))
            ->where('stats', ['draft' => 1, 'scheduled' => 1, 'published' => 1, 'completed' => 1, 'archived' => 1])
            ->where('canManage', true)
            ->has('options.modules')
            ->has('options.groups'));
});

test('students only see the published exams and history of their own group', function () {
    $this->actingAs($this->student)
        ->get(route('exams.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('exams', examIds($this->exams['published']))
            ->where('canManage', false)
            ->where('options', null));
});

test('teachers only see the published exams and history of the modules they teach', function () {
    $this->actingAs($this->teacher)
        ->get(route('exams.index'))
        ->assertInertia(fn (Assert $page) => $page->where('exams', examIds($this->exams['completed'])));
});

test('the exam list filters by state, program and group', function () {
    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($coordinator)
        ->get(route('exams.index', ['state' => 'scheduled']))
        ->assertInertia(fn (Assert $page) => $page->where('exams', examIds($this->exams['scheduled'])));

    $this->actingAs($coordinator)
        ->get(route('exams.index', ['group_id' => $this->otherGroup->id]))
        ->assertInertia(fn (Assert $page) => $page->where('exams', examIds($this->exams['completed'], $this->exams['archived'])));

    $this->actingAs($coordinator)
        ->get(route('exams.index', ['program_id' => Program::factory()->create()->id]))
        ->assertInertia(fn (Assert $page) => $page->where('exams', examIds()));
});

test('the page opens on the earliest period not yet ended, unless one is asked for', function () {
    $ended = ExamPeriod::factory()->between('2026-06-01', '2026-06-30')->create();
    ExamPeriod::factory()->between('2026-12-01', '2026-12-20')->create();
    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($coordinator)
        ->get(route('exams.index'))
        ->assertInertia(fn (Assert $page) => $page->where('periodId', $this->period->id)->has('periods', 3));

    $this->actingAs($coordinator)
        ->get(route('exams.index', ['period' => $ended->id]))
        ->assertInertia(fn (Assert $page) => $page->where('periodId', $ended->id)->where('exams', []));
});
