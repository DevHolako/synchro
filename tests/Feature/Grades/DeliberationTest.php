<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Grades\StoreDeliberationPvAction;
use App\Enums\ExamState;
use App\Enums\GradeSheetStatus;
use App\Enums\Permission;
use App\Models\Exam;
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
    $this->alami = StudentProfile::factory()->create(['student_group_id' => $group->id, 'last_name' => 'Alami'])->user;
    $this->zerouali = StudentProfile::factory()->create(['student_group_id' => $group->id, 'last_name' => 'Zerouali'])->user;
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
 * Opens the sheet, grades Alami 12/15 (final 13.80) and Zerouali absent with CC 10 (final 4.00),
 * and submits it.
 */
function submitGradedSheet(Exam $exam, User $teacher, User $alami, User $zerouali): void
{
    test()->actingAs($teacher)->get(route('exams.grades.show', $exam));
    test()->actingAs($teacher)->put(route('exams.grades.update', $exam), ['grades' => [
        ['student_id' => $alami->id, 'continuous_assessment_grade' => '12', 'exam_grade' => '15', 'is_absent' => false, 'remarks' => null],
        ['student_id' => $zerouali->id, 'continuous_assessment_grade' => '10', 'exam_grade' => null, 'is_absent' => true, 'remarks' => 'Malade'],
    ]])->assertSessionHasNoErrors();
    test()->actingAs($teacher)->post(route('exams.grades.submit', $exam))->assertSessionHasNoErrors();
}

test('the coordinator locks a submitted deliberation and its PV is archived', function () {
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $this->exam))->assertSessionHasNoErrors();

    $sheet = $this->exam->deliberation()->sole();

    expect($sheet->status)->toBe(GradeSheetStatus::Locked)
        ->and($sheet->locked_by)->toBe($this->coordinator->id)
        ->and($sheet->locked_at)->not->toBeNull()
        ->and($sheet->continuous_assessment_weight)->toBe(40)
        ->and($sheet->class_average)->toBe('8.90')
        ->and($sheet->pass_rate)->toBe('50.00')
        ->and($sheet->pv_document_path)->toBe("deliberation-pvs/{$this->exam->id}.pdf");

    $pdf = Storage::disk('local')->get($sheet->pvPath());

    expect($pdf)->toStartWith('%PDF')
        ->and($sheet->pv_sha256)->toBe(hash('sha256', (string) $pdf))
        ->and(app(StoreDeliberationPvAction::class)->execute($sheet))->toBeFalse();
});

test('only a submitted sheet can be locked or sent back, and only by those who lock grades', function () {
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam));

    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $this->exam))
        ->assertSessionHasErrors(['deliberation' => __('messages.deliberation_not_submitted')]);
    $this->actingAs($this->coordinator)->post(route('exams.grades.return', $this->exam), ['reason' => 'Revoir les notes de CC.'])
        ->assertSessionHasErrors('deliberation');

    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->teacher)->post(route('exams.deliberation.lock', $this->exam))->assertForbidden();
    $this->actingAs($this->teacher)->post(route('exams.grades.return', $this->exam), ['reason' => 'Revoir les notes de CC.'])->assertForbidden();

    expect($this->coordinator->hasPermission(Permission::LockGrades))->toBeTrue()
        ->and($this->exam->deliberation()->sole()->status)->toBe(GradeSheetStatus::Submitted);
});

test('a locked deliberation is immutable', function () {
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);
    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $this->exam));

    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->exam), ['grades' => [
        ['student_id' => $this->alami->id, 'continuous_assessment_grade' => '20', 'exam_grade' => '20', 'is_absent' => false, 'remarks' => null],
    ]])->assertForbidden();
    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $this->exam))->assertForbidden();
    $this->actingAs($this->coordinator)->post(route('exams.grades.return', $this->exam), ['reason' => 'Revoir les notes de CC.'])
        ->assertSessionHasErrors('deliberation');

    $grade = ExamGrade::query()->where('exam_id', $this->exam->id)->where('student_id', $this->alami->id)->sole();
    $sheet = $this->exam->deliberation()->sole();

    expect(fn () => $grade->update(['exam_grade' => '20.00']))->toThrow(LogicException::class)
        ->and(fn () => $grade->delete())->toThrow(LogicException::class)
        ->and(fn () => $sheet->update(['status' => GradeSheetStatus::Draft]))->toThrow(LogicException::class)
        ->and(fn () => $sheet->delete())->toThrow(LogicException::class)
        ->and($grade->fresh()->final_grade)->toBe('13.80');
});

test('a sheet sent back returns to its teacher as a draft with the reason', function () {
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->coordinator)->post(route('exams.grades.return', $this->exam), ['reason' => 'Court'])
        ->assertSessionHasErrors('reason');
    $this->actingAs($this->coordinator)->post(route('exams.grades.return', $this->exam), ['reason' => 'Revoir les notes de CC du groupe.'])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sheet.status', 'draft')
            ->where('can_edit', true)
            ->where('deliberation.return_reason', 'Revoir les notes de CC du groupe.'));

    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $this->exam))->assertSessionHasNoErrors();

    $sheet = $this->exam->deliberation()->sole();

    expect($sheet->status)->toBe(GradeSheetStatus::Submitted)
        ->and($sheet->return_reason)->toBeNull();
});

test('the coordinator sees the deliberation figures and may decide on a submitted sheet', function () {
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->coordinator)->get(route('exams.grades.show', $this->exam))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can_edit', false)
            ->where('deliberation.can_decide', true)
            ->where('deliberation.stats.average', '8.90')
            ->where('deliberation.stats.median', '8.90')
            ->where('deliberation.stats.absent', 1));

    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->exam))
        ->assertInertia(fn (Assert $page) => $page->where('deliberation.can_decide', false));
});

test('students see their grades only once the deliberation is locked', function () {
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->alami)->get(route('my-grades.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('my-grades/index')->where('grades', []));

    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $this->exam));

    $this->actingAs($this->alami)->get(route('my-grades.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('grades', 1)
            ->where('grades.0.final_grade', '13.80')
            ->where('grades.0.continuous_assessment_weight', 40)
            ->where('grades.0.passed', true));

    $this->actingAs($this->zerouali)->get(route('my-grades.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('grades.0.is_absent', true)
            ->where('grades.0.passed', false));

    $this->actingAs($this->alami)->get(route('exams.grades.show', $this->exam))->assertForbidden();
    $this->actingAs($this->teacher)->get(route('my-grades.index'))->assertForbidden();
});

test('the PV is for coordinators and the module teacher, once locked', function () {
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->coordinator)->get(route('exams.pv', $this->exam))->assertForbidden();

    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $this->exam));

    $this->actingAs($this->coordinator)->get(route('exams.pv', $this->exam))->assertOk()->assertDownload('pv-'.$this->module->code.'.pdf');
    $this->actingAs($this->teacher)->get(route('exams.pv', $this->exam))->assertOk();
    $this->actingAs($this->alami)->get(route('exams.pv', $this->exam))->assertForbidden();
    $this->actingAs(User::factory()->teacher()->create())->get(route('exams.pv', $this->exam))->assertForbidden();
});

test('the deliberation board lists finished exams, sheets to decide first', function () {
    $later = Exam::factory()->completed()->between('2026-10-13 09:00', '2026-10-13 11:00')->create([
        'exam_period_id' => $this->exam->exam_period_id,
        'module_id' => Module::factory()->create()->id,
    ]);
    submitGradedSheet($this->exam, $this->teacher, $this->alami, $this->zerouali);

    $this->actingAs($this->coordinator)->get(route('deliberations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('deliberations/index')
            ->where('period_id', $this->exam->exam_period_id)
            ->where('stats', ['submitted' => 1, 'draft' => 0, 'not_started' => 1, 'locked' => 0])
            ->where('sheets.0.exam_id', $this->exam->id)
            ->where('sheets.0.status', 'submitted')
            ->where('sheets.0.lines', 2)
            ->where('sheets.1.exam_id', $later->id)
            ->where('sheets.1.status', 'not_started'));

    $this->actingAs($this->coordinator)->get(route('deliberations.index', ['status' => 'not_started']))
        ->assertInertia(fn (Assert $page) => $page->has('sheets', 1)->where('sheets.0.exam_id', $later->id));

    $this->actingAs($this->teacher)->get(route('deliberations.index'))->assertForbidden();
    expect(ExamDeliberation::query()->count())->toBe(1);
});
