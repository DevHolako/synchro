<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\StudentGroup;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Support\SessionKey;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

function usersCsv(array $lines): UploadedFile
{
    return UploadedFile::fake()->createWithContent('users.csv', implode("\n", array_map(fn (array $l) => implode(',', $l), $lines))."\n");
}

test('teachers are imported as invited accounts with profiles and invitations', function () {
    $department = Department::factory()->create(['code' => 'ISI']);

    $this->actingAs($this->admin)->post(route('imports.store', 'teachers'), ['file' => usersCsv([
        ['name', 'email', 'department_code', 'employee_number', 'phone'],
        ['Amina El Idrissi', 'Amina@isga.ma', 'ISI', 'ENS-001', '0612345678'],
        ['Karim Alaoui', 'karim@isga.ma', '', '', ''],
    ])])->assertRedirect();

    $amina = User::where('email', 'amina@isga.ma')->sole();

    expect($amina->role)->toBe(UserRole::Teacher)
        ->and($amina->status)->toBe(AccountStatus::Invited)
        ->and($amina->teacherProfile->department_id)->toBe($department->id)
        ->and($amina->teacherProfile->employee_number)->toBe('ENS-001')
        ->and(User::where('email', 'karim@isga.ma')->sole()->teacherProfile)->not->toBeNull();

    Notification::assertSentTo($amina, UserInvitationNotification::class);
    Notification::assertCount(2);
});

test('students are imported with their group assignment', function () {
    $group = StudentGroup::factory()->create(['code' => '1CI-G1']);

    $this->actingAs($this->admin)->post(route('imports.store', 'students'), ['file' => usersCsv([
        ['name', 'email', 'group_code', 'student_number'],
        ['Youssef Benali', 'youssef@isga.ma', '1CI-G1', 'ETU-1'],
        ['Sara Tazi', 'sara@isga.ma', '1CI-G1', 'ETU-2'],
    ])])->assertRedirect();

    expect(User::where('role', UserRole::Student)->count())->toBe(2)
        ->and(User::where('email', 'sara@isga.ma')->sole()->studentProfile->student_group_id)->toBe($group->id);

    Notification::assertCount(2);
});

test('a failing user import creates no accounts and sends no invitations', function () {
    StudentGroup::factory()->create(['code' => '1CI-G1']);
    User::factory()->create(['email' => 'taken@isga.ma']);

    $this->actingAs($this->admin)->post(route('imports.store', 'students'), ['file' => usersCsv([
        ['name', 'email', 'group_code', 'student_number'],
        ['Valid Student', 'valid@isga.ma', '1CI-G1', 'ETU-1'],
        ['Unknown Group', 'unknown@isga.ma', 'NOPE', ''],
        ['Taken Email', 'taken@isga.ma', '1CI-G1', ''],
        ['Duplicate Number', 'dup@isga.ma', '1CI-G1', 'etu-1'],
        ['', 'not-an-email', '1CI-G1', ''],
    ])])->assertRedirect();

    $errors = collect(session(SessionKey::FLASH_DATA)['import_report']['errors'])
        ->map(fn (array $e) => [$e['row'], $e['column']])
        ->all();

    expect($errors)->toBe([
        [3, 'group_code'],
        [4, 'email'],
        [5, 'student_number'],
        [6, 'name'],
        [6, 'email'],
    ])->and(User::whereIn('email', ['valid@isga.ma', 'unknown@isga.ma', 'dup@isga.ma'])->exists())->toBeFalse();

    Notification::assertNothingSent();
});
