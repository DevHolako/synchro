<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Grades\ListRetakeCandidatesAction;
use App\Actions\Grades\RenderDeliberationPvPdfAction;
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
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->alami = StudentProfile::factory()->create(['student_group_id' => $this->group->id, 'last_name' => 'Alami'])->user;
    $this->zerouali = StudentProfile::factory()->create(['student_group_id' => $this->group->id, 'last_name' => 'Zerouali'])->user;
    $this->module = Module::factory()->create([
        'program_id' => $program->id,
        'teacher_id' => $this->teacher->id,
        'continuous_assessment_weight' => 40,
    ]);
    $this->room = Room::factory()->create(['course_capacity' => 30, 'exam_capacity' => 10]);
    $this->normalExam = retakeTestExam(
        ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create(['academic_year' => '2026-2027']),
        $this->module,
        $this->group,
        $this->room,
        '2026-10-12',
    );

    // Alami passes (13.80); Zerouali is absent with CC 10 (4.00).
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $this->normalExam));
    $this->actingAs($this->teacher)->put(route('exams.grades.update', $this->normalExam), ['grades' => [
        ['student_id' => $this->alami->id, 'continuous_assessment_grade' => '12', 'exam_grade' => '15', 'is_absent' => false, 'remarks' => null],
        ['student_id' => $this->zerouali->id, 'continuous_assessment_grade' => '10', 'exam_grade' => null, 'is_absent' => true, 'remarks' => 'Malade'],
    ]]);
    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $this->normalExam));
    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $this->normalExam))->assertSessionHasNoErrors();

    $this->retakePeriod = ExamPeriod::factory()->retake()->between('2026-11-01', '2026-11-30')->create(['academic_year' => '2026-2027']);
});

/**
 * A completed exam of the module for the group, seated in the room.
 */
function retakeTestExam(ExamPeriod $period, Module $module, StudentGroup $group, Room $room, string $day): Exam
{
    $exam = Exam::factory()->between("{$day} 09:00", "{$day} 11:00")->forGroups($group)->create([
        'exam_period_id' => $period->id,
        'module_id' => $module->id,
    ]);
    app(AllocateExamRoomsAction::class)->execute($exam, [$room->id]);
    $exam->update(['state' => ExamState::Completed]);

    return $exam;
}

test('retake candidates are the locked normal-session finals below 10 of the academic year', function () {
    $candidates = app(ListRetakeCandidatesAction::class)->execute('2026-2027');

    expect($candidates->pluck('student_id')->all())->toBe([$this->zerouali->id])
        ->and($candidates->first()->final_grade)->toBe('4.00')
        ->and(app(ListRetakeCandidatesAction::class)->execute('2025-2026'))->toBeEmpty();
});

test('failing grades of a sheet not yet locked do not make retake candidates', function () {
    $otherModule = Module::factory()->create(['program_id' => $this->module->program_id, 'teacher_id' => $this->teacher->id]);
    $exam = retakeTestExam($this->normalExam->examPeriod, $otherModule, StudentGroup::factory()->create(['program_id' => $this->module->program_id]), $this->room, '2026-10-14');
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $exam));
    ExamGrade::query()->where('exam_id', $exam->id)->update(['final_grade' => '2.00']);

    expect(app(ListRetakeCandidatesAction::class)->execute('2026-2027', [$otherModule->id]))->toBeEmpty();
});

test('when a module was examined twice in the year, the latest locked line decides', function () {
    $resit = retakeTestExam($this->normalExam->examPeriod, $this->module, StudentGroup::factory()->create(['program_id' => $this->module->program_id]), $this->room, '2026-10-20');
    ExamGrade::factory()->for($resit)->for($this->zerouali, 'student')->withFinal('13.00')->create();
    ExamGrade::factory()->for($resit)->for($this->alami, 'student')->withFinal('5.00')->create();
    ExamDeliberation::factory()->for($resit)->locked()->create();

    expect(app(ListRetakeCandidatesAction::class)->execute('2026-2027')->pluck('student_id')->all())->toBe([$this->alami->id]);
});

test('locked grade lines and deliberations refuse bulk writes too', function () {
    $lines = ExamGrade::query()->where('exam_id', $this->normalExam->id);
    $sheet = ExamDeliberation::query()->where('exam_id', $this->normalExam->id);

    expect(fn () => (clone $lines)->update(['final_grade' => '20.00']))->toThrow(LogicException::class)
        ->and(fn () => (clone $lines)->delete())->toThrow(LogicException::class)
        ->and(fn () => ExamGrade::query()->upsert(
            [['exam_id' => $this->normalExam->id, 'student_id' => $this->zerouali->id, 'final_grade' => '20.00']],
            ['exam_id', 'student_id'],
            ['final_grade'],
        ))->toThrow(LogicException::class)
        ->and(fn () => (clone $sheet)->update(['status' => GradeSheetStatus::Draft]))->toThrow(LogicException::class)
        ->and(fn () => (clone $sheet)->update(['pv_sha256' => str_repeat('0', 64)]))->toThrow(LogicException::class)
        ->and(fn () => (clone $sheet)->delete())->toThrow(LogicException::class)
        ->and(fn () => (clone $sheet)->increment('continuous_assessment_weight'))->toThrow(LogicException::class)
        ->and(fn () => (clone $lines)->increment('final_grade'))->toThrow(LogicException::class)
        ->and(fn () => ExamGrade::factory()->for($this->normalExam)->create())->toThrow(LogicException::class)
        ->and(fn () => ExamGrade::query()->insert(['exam_id' => $this->normalExam->id, 'student_id' => User::factory()->student()->create()->id]))->toThrow(LogicException::class)
        ->and(fn () => ExamGrade::query()->insertOrIgnore(['exam_id' => $this->normalExam->id, 'student_id' => $this->alami->id]))->toThrow(LogicException::class)
        ->and(ExamGrade::query()->where('exam_id', $this->normalExam->id)->where('student_id', $this->zerouali->id)->value('final_grade'))->toBe('4.00')
        ->and(ExamGrade::query()->where('exam_id', $this->normalExam->id)->count())->toBe(2);
});

test('failing students without a group are counted on the roster', function () {
    $this->zerouali->studentProfile->update(['student_group_id' => null]);

    $this->actingAs($this->coordinator)->get(route('retakes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('modules.0.ungrouped', 1)
            ->where('modules.0.group_ids', []));
});

test('a retake PV says who failed rather than who goes to the retake', function () {
    $retake = retakeTestExam($this->retakePeriod, $this->module, $this->group, $this->room, '2026-11-10');
    $this->actingAs($this->teacher)->get(route('exams.grades.show', $retake));
    $this->actingAs($this->teacher)->put(route('exams.grades.update', $retake), ['grades' => [
        ['student_id' => $this->zerouali->id, 'continuous_assessment_grade' => null, 'exam_grade' => '2', 'is_absent' => false, 'remarks' => null],
    ]]);
    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $retake));
    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $retake))->assertSessionHasNoErrors();

    $data = app(RenderDeliberationPvPdfAction::class)->viewData($retake->deliberation()->sole());
    $html = view('pdf.deliberation-pv', $data)->render();

    expect($data['retake'])->toBeTrue()
        ->and($data['lines'][0]['passed'])->toBeFalse()
        ->and($html)->toContain(__('documents.pv_title_retake', [], 'fr'))
        ->and($html)->toContain(__('documents.pv_counts_retake', [], 'fr'))
        ->and($html)->toContain(__('documents.pv_failed', [], 'fr'))
        ->and($html)->not->toContain('>'.__('documents.pv_retake', [], 'fr').'<');
});

test('the retake roster lists each module\'s failing students for exam managers', function () {
    $this->actingAs($this->coordinator)->get(route('retakes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('retakes/index')
            ->where('period.id', $this->retakePeriod->id)
            ->has('modules', 1)
            ->where('modules.0.module_id', $this->module->id)
            ->where('modules.0.group_ids', [$this->group->id])
            ->where('modules.0.exam', null)
            ->where('modules.0.ungrouped', 0)
            ->has('modules.0.students', 1)
            ->where('modules.0.students.0.student_id', $this->zerouali->id)
            ->where('modules.0.students.0.final_grade', '4.00'));

    $this->actingAs($this->teacher)->get(route('retakes.index'))->assertForbidden();
});

test('a retake exam seats only the students who failed the module', function () {
    $this->actingAs($this->coordinator)->post(route('exams.store'), [
        'exam_period_id' => $this->retakePeriod->id,
        'module_id' => $this->module->id,
        'student_group_ids' => [$this->group->id],
        'starts_at' => '2026-11-10 09:00',
        'ends_at' => '2026-11-10 11:00',
    ])->assertSessionHasNoErrors();

    $retake = Exam::query()->where('exam_period_id', $this->retakePeriod->id)->sole();
    app(AllocateExamRoomsAction::class)->execute($retake, [$this->room->id]);

    expect(ExamCandidate::query()->where('exam_id', $retake->id)->pluck('student_id')->all())->toBe([$this->zerouali->id]);

    $this->actingAs($this->coordinator)->get(route('retakes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('modules.0.exam', ['id' => $retake->id, 'state' => 'draft']));
});

test('a retake sheet carries CC over and keeps the better final', function () {
    $retake = retakeTestExam($this->retakePeriod, $this->module, $this->group, $this->room, '2026-11-10');

    $this->actingAs($this->teacher)->get(route('exams.grades.show', $retake))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('exam.retake', true)
            ->has('rows', 1)
            ->where('rows.0.continuous_assessment_grade', '10.00')
            ->where('rows.0.previous_final_grade', '4.00'));

    // The CC sent is ignored: it stays the normal session's.
    $this->actingAs($this->teacher)->put(route('exams.grades.update', $retake), ['grades' => [
        ['student_id' => $this->zerouali->id, 'continuous_assessment_grade' => '20', 'exam_grade' => '12', 'is_absent' => false, 'remarks' => null],
    ]])->assertSessionHasNoErrors();

    $line = ExamGrade::query()->where('exam_id', $retake->id)->sole();

    expect($line->continuous_assessment_grade)->toBe('10.00')
        ->and($line->final_grade)->toBe('11.20');

    $this->actingAs($this->teacher)->post(route('exams.grades.submit', $retake))->assertSessionHasNoErrors();
    $this->actingAs($this->coordinator)->post(route('exams.deliberation.lock', $retake))->assertSessionHasNoErrors();

    $this->actingAs($this->zerouali)->get(route('my-grades.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('grades', 2)
            ->where('grades.0.session_type', 'rattrapage')
            ->where('grades.0.final_grade', '11.20')
            ->where('grades.0.passed', true)
            ->where('grades.1.session_type', 'normal'));

    expect(Storage::disk('local')->exists("deliberation-pvs/{$retake->id}.pdf"))->toBeTrue();
});

test('the deliberation board lists retake periods too', function () {
    $this->actingAs($this->coordinator)->get(route('deliberations.index', ['period' => $this->retakePeriod->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('period_id', $this->retakePeriod->id)
            ->where('periods.0.session_type', 'rattrapage'));
});
