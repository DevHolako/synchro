<?php

use App\Enums\AttendanceStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\SessionAttendance;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;

beforeEach(function () {
    $this->travelTo('2026-10-12 11:00');
    $this->coordinator = User::factory()->coordinator()->create();
    $this->teacher = User::factory()->teacher()->create();
    $program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $program->id]);
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->students = collect(range(1, 3))
        ->map(fn () => StudentProfile::factory()->create(['student_group_id' => $this->group->id])->user)
        ->sortBy('name')
        ->values();
    $this->session = attendanceSession('2026-10-12 10:00', '2026-10-12 12:00');
});

function attendanceSession(string $startsAt, string $endsAt): CourseSession
{
    return CourseSession::factory()->between($startsAt, $endsAt)->forGroups(test()->group)->create([
        'module_id' => test()->module->id,
        'teacher_id' => test()->teacher->id,
    ]);
}

/**
 * @param  array<int, array{0: User, 1: AttendanceStatus, 2?: string}>  $marks
 */
function markPayload(array $marks): array
{
    return ['marks' => array_map(fn (array $mark) => [
        'student_id' => $mark[0]->id,
        'status' => $mark[1]->value,
        'remarks' => $mark[2] ?? null,
    ], $marks)];
}

test('the session teacher sees the register: the groups students, unmarked', function () {
    $this->actingAs($this->teacher)
        ->getJson(route('course-sessions.attendance.show', $this->session))
        ->assertOk()
        ->assertJsonCount(3, 'students')
        ->assertJsonPath('students.0.student_id', $this->students[0]->id)
        ->assertJsonPath('students.0.group', $this->group->name)
        ->assertJsonPath('students.0.status', null)
        ->assertJsonPath('students.0.module_recorded', 0);
});

test('marking everyone present saves one mark per student', function () {
    $this->actingAs($this->teacher)
        ->putJson(route('course-sessions.attendance.update', $this->session), markPayload(
            $this->students->map(fn (User $student) => [$student, AttendanceStatus::Present])->all(),
        ))
        ->assertNoContent();

    expect(SessionAttendance::count())->toBe(3)
        ->and(SessionAttendance::query()->pluck('recorded_by')->unique()->all())->toBe([$this->teacher->id]);
});

test('saving again replaces the marks instead of duplicating them', function () {
    $route = route('course-sessions.attendance.update', $this->session);
    $this->actingAs($this->teacher)->putJson($route, markPayload([[$this->students[0], AttendanceStatus::Present]]));

    $this->actingAs($this->coordinator)
        ->putJson($route, markPayload([[$this->students[0], AttendanceStatus::Late, 'Arrived at 10:20.']]))
        ->assertNoContent();

    $mark = SessionAttendance::query()->sole();

    expect($mark->status)->toBe(AttendanceStatus::Late)
        ->and($mark->remarks)->toBe('Arrived at 10:20.')
        ->and($mark->recorded_by)->toBe($this->coordinator->id);
});

test('each student shows how often they missed this module', function () {
    $earlier = attendanceSession('2026-10-05 10:00', '2026-10-05 12:00');
    $otherModule = CourseSession::factory()->between('2026-10-06 10:00', '2026-10-06 12:00')->create();
    SessionAttendance::factory()->create(['course_session_id' => $earlier->id, 'student_id' => $this->students[0]->id, 'status' => AttendanceStatus::Absent]);
    SessionAttendance::factory()->create(['course_session_id' => $otherModule->id, 'student_id' => $this->students[0]->id, 'status' => AttendanceStatus::Absent]);
    SessionAttendance::factory()->create(['course_session_id' => $this->session->id, 'student_id' => $this->students[0]->id, 'status' => AttendanceStatus::Late]);

    $this->actingAs($this->teacher)
        ->getJson(route('course-sessions.attendance.show', $this->session))
        ->assertJsonPath('students.0.status', 'late')
        ->assertJsonPath('students.0.module_absences', 1)
        ->assertJsonPath('students.0.module_recorded', 2)
        ->assertJsonPath('students.0.module_absence_rate', 50)
        ->assertJsonPath('students.1.module_absence_rate', 0);
});

test('a mark sent without a status is removed with its remark, and other marks are left alone', function () {
    $route = route('course-sessions.attendance.update', $this->session);
    $this->actingAs($this->teacher)->putJson($route, markPayload([
        [$this->students[0], AttendanceStatus::Present],
        [$this->students[1], AttendanceStatus::Absent, 'Sick.'],
    ]));

    $this->actingAs($this->teacher)
        ->putJson($route, ['marks' => [['student_id' => $this->students[1]->id, 'status' => null, 'remarks' => 'Sick.']]])
        ->assertNoContent();

    expect(SessionAttendance::query()->pluck('student_id')->all())->toBe([$this->students[0]->id]);
});

test('the register opens by the school clock, not the server clock', function () {
    // 08:30 UTC is 10:30 in Paris (summer time): the 10:00 session has started there.
    config(['app.schedule_timezone' => 'Europe/Paris']);
    $this->travelTo('2026-10-12 08:30');

    $this->actingAs($this->teacher)
        ->putJson(route('course-sessions.attendance.update', $this->session), markPayload([[$this->students[0], AttendanceStatus::Present]]))
        ->assertNoContent();
});

test('attendance opens only once the session has started', function () {
    $later = attendanceSession('2026-10-12 14:00', '2026-10-12 16:00');

    $this->actingAs($this->teacher)
        ->putJson(route('course-sessions.attendance.update', $later), markPayload([[$this->students[0], AttendanceStatus::Present]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('marks');

    expect(SessionAttendance::count())->toBe(0);
});

test('only students of the session can be marked', function () {
    $outsider = StudentProfile::factory()->create()->user;

    $this->actingAs($this->teacher)
        ->putJson(route('course-sessions.attendance.update', $this->session), markPayload([[$outsider, AttendanceStatus::Present]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('marks');
});

test('invalid marks are rejected', function () {
    $this->actingAs($this->teacher)
        ->putJson(route('course-sessions.attendance.update', $this->session), ['marks' => [
            ['student_id' => $this->students[0]->id, 'status' => 'sleeping'],
        ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('marks.0.status');
});

test('another teacher or a student cannot take this register', function (string $who) {
    $user = $who === 'teacher' ? User::factory()->teacher()->create() : $this->students[0];

    $this->actingAs($user)->getJson(route('course-sessions.attendance.show', $this->session))->assertForbidden();
    $this->actingAs($user)
        ->putJson(route('course-sessions.attendance.update', $this->session), markPayload([[$this->students[0], AttendanceStatus::Present]]))
        ->assertForbidden();
})->with(['teacher', 'student']);

test('teachers and coordinators hold the attendance permission, students do not', function (UserRole $role, bool $records) {
    expect(User::factory()->create(['role' => $role])->hasPermission(Permission::RecordAttendance))->toBe($records);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'coordinator' => [UserRole::Coordinator, true],
    'teacher' => [UserRole::Teacher, true],
    'student' => [UserRole::Student, false],
]);

test('students on a register keep their scheduling history', function () {
    SessionAttendance::factory()->create(['course_session_id' => $this->session->id, 'student_id' => $this->students[0]->id]);

    expect($this->students[0]->hasSchedulingHistory())->toBeTrue()
        ->and($this->students[1]->hasSchedulingHistory())->toBeFalse();
});
