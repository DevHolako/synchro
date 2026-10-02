<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Modules\UpdateModuleAction;
use App\Enums\ExamState;
use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamDeliberation;
use App\Models\ExamGrade;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00');
    config(['app.schedule_timezone' => 'UTC']);
    Storage::fake('local');

    $this->teacher = User::factory()->teacher()->create();
    $this->coordinator = User::factory()->coordinator()->create();
    $program = Program::factory()->create();
    $group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->zerouali = StudentProfile::factory()->create(['student_group_id' => $group->id, 'last_name' => 'Zerouali'])->user;
    $this->alami = StudentProfile::factory()->create(['student_group_id' => $group->id, 'last_name' => 'Élalami'])->user;
    $this->module = Module::factory()->create([
        'program_id' => $program->id,
        'teacher_id' => $this->teacher->id,
        'continuous_assessment_weight' => 40,
    ]);
    $this->exam = Exam::factory()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($group)->create([
        'exam_period_id' => ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create()->id,
        'module_id' => $this->module->id,
    ]);
    app(AllocateExamRoomsAction::class)->execute($this->exam, [
        Room::factory()->create(['course_capacity' => 30, 'exam_capacity' => 10])->id,
    ]);
    $this->exam->update(['state' => ExamState::Completed]);
});

/**
 * @return array{student_id: int, continuous_assessment_grade: string|null, exam_grade: string|null, is_absent: bool, remarks: string|null}
 */
function gradeLine(User $student, ?string $continuousAssessment, ?string $exam, bool $absent = false, ?string $remarks = null): array
{
    return [
        'student_id' => $student->id,
        'continuous_assessment_grade' => $continuousAssessment,
        'exam_grade' => $exam,
        'is_absent' => $absent,
        'remarks' => $remarks,
    ];
}

function gradeOf(Exam $exam, User $student): ExamGrade
{
    return ExamGrade::query()->where('exam_id', $exam->id)->where('student_id', $student->id)->sole();
}

test('the module teacher opens a grid of every candidate in official order', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('grades/show')
            ->where('weights', ['continuous_assessment' => 40, 'exam' => 60])
            ->where('sheet.status', 'draft')
            ->where('can_edit', true)
            ->where('rows.0.student_id', $this->alami->id)
            ->where('rows.1.student_id', $this->zerouali->id)
            ->where('rows.0.is_absent', false)
            ->where('rows.1.is_absent', false));

    expect(ExamGrade::query()->where('exam_id', $this->exam->id)->count())->toBe(2);
});

test('candidates who never checked in start absent once the door check-in was used', function () {
    ExamCandidate::query()->where('student_id', $this->alami->id)->update(['checked_in_at' => now()]);

    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))->assertOk();

    expect(gradeOf($this->exam, $this->alami)->is_absent)->toBeFalse()
        ->and(gradeOf($this->exam, $this->zerouali)->is_absent)->toBeTrue();
});

test('the teacher saves changed lines and the server computes the finals', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));

    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine($this->alami, '12', '15,5'),
        gradeLine($this->zerouali, '14', '18', true, 'Malade'),
    ]])->assertSessionHasNoErrors();

    $alami = gradeOf($this->exam, $this->alami);
    $zerouali = gradeOf($this->exam, $this->zerouali);

    expect($alami->exam_grade)->toBe('15.50')
        ->and($alami->final_grade)->toBe('14.10')
        ->and($zerouali->exam_grade)->toBeNull()
        ->and($zerouali->final_grade)->toBe('5.60')
        ->and($zerouali->remarks)->toBe('Malade');
});

test('grades must be between 0 and 20 with at most two decimals', function (string $grade) {
    app()->setLocale('fr');
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));

    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine($this->alami, '12', $grade),
    ]])->assertSessionHasErrors(['grades.0.exam_grade' => __('messages.grade_invalid', ['max' => 20])]);

    expect(gradeOf($this->exam, $this->alami)->exam_grade)->toBeNull();
})->with(['20.5', '12.345', '-1', 'douze']);

test('only students on the sheet can be graded', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));

    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine(User::factory()->student()->create(), '12', '12'),
    ]])->assertSessionHasErrors('grades');
});

test('only the module teacher enters grades; exam managers read the sheet', function () {
    $this->actingAs($this->coordinator)->get(route('exams.grades.show', $this->exam))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('can_edit', false));

    foreach ([$this->coordinator, User::factory()->teacher()->create()] as $user) {
        $this->actingAs($user)->put(route('exams.grades.update', $this->exam), ['grades' => [
            gradeLine($this->alami, '12', '12'),
        ]])->assertForbidden();

        $this->actingAs($user)->post(route('exams.grades.submit', $this->exam))->assertForbidden();
    }

    $this->actingAs(User::factory()->teacher()->create())->get(route('exams.grades.show', $this->exam))->assertForbidden();
    $this->actingAs($this->alami)->get(route('exams.grades.show', $this->exam))->assertForbidden();
});

test('the grid opens only once the exam is over, and not for retake sessions', function () {
    $this->exam->update(['state' => ExamState::Published]);
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))->assertForbidden();

    $this->exam->update(['state' => ExamState::Completed, 'exam_period_id' => ExamPeriod::factory()->retake()->create()->id]);
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))->assertForbidden();

    expect(ExamDeliberation::query()->count())->toBe(0);
});

test('an incomplete sheet cannot be submitted and names who is missing', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));

    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine($this->alami, '12', '15'),
        gradeLine($this->zerouali, '14', null, true),
    ]]);

    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $this->exam))
        ->assertSessionHasErrors(['grades' => __('messages.grade_sheet_incomplete', [
            'count' => 1,
            'names' => $this->zerouali->officialName(),
        ])]);

    expect($this->exam->deliberation()->sole()->status)->toBe(GradeSheetStatus::Draft);
});

test('a submitted sheet is read-only for its teacher', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));
    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine($this->alami, '12', '15'),
        gradeLine($this->zerouali, '14', null, true, 'Malade'),
    ]]);

    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $this->exam))->assertSessionHasNoErrors();

    $sheet = $this->exam->deliberation()->sole();

    expect($sheet->status)->toBe(GradeSheetStatus::Submitted)
        ->and($sheet->submitted_by)->toBe($this->teacher->id)
        ->and($sheet->submitted_at)->not->toBeNull();

    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine($this->alami, '20', '20'),
    ]])->assertSessionHasErrors(['grades' => __('messages.grade_sheet_not_draft')]);

    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $this->exam))->assertSessionHasErrors('grades');

    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sheet.status', 'submitted')
            ->where('sheet.submitted_by', $this->teacher->name)
            ->where('can_edit', false));

    expect(gradeOf($this->exam, $this->alami)->final_grade)->toBe('13.80');
});

test('a weighting change recomputes open sheets and leaves locked ones alone', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));
    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        gradeLine($this->alami, '10', '20'),
    ]]);

    $locked = Exam::factory()->completed()->create(['module_id' => $this->module->id]);
    ExamDeliberation::query()->create(['exam_id' => $locked->id, 'status' => GradeSheetStatus::Locked]);
    $lockedGrade = ExamGrade::query()->create([
        'exam_id' => $locked->id,
        'student_id' => $this->alami->id,
        'continuous_assessment_grade' => '10.00',
        'exam_grade' => '20.00',
        'final_grade' => '16.00',
    ]);

    app(UpdateModuleAction::class)->execute($this->module, ['continuous_assessment_weight' => 50]);

    expect(gradeOf($this->exam, $this->alami)->final_grade)->toBe('15.00')
        ->and($lockedGrade->fresh()->final_grade)->toBe('16.00');
});

test('the exams list offers the grid to the module teacher once the exam is over', function () {
    $this->actingAs($this->teacher)->get(route('exams.index', ['period' => $this->exam->exam_period_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('exams.0.grades', ['status' => null, 'can_enter' => true]));

    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));

    $this->actingAs($this->coordinator)->get(route('exams.index', ['period' => $this->exam->exam_period_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('exams.0.grades', ['status' => 'draft', 'can_enter' => false]));
});
