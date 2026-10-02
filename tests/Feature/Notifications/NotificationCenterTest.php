<?php

use App\Actions\Exams\PublishExamAction;
use App\Actions\Notifications\NotifyTimetablePublishedAction;
use App\Enums\ExamSessionType;
use App\Enums\ExamState;
use App\Enums\InvigilatorRole;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\ExamRoomAssignment;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ExamConvocationPublishedNotification;
use App\Notifications\TimetablePublishedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->student()->create();
});

test('it lists recent notifications and unread count for authenticated user', function () {
    // Generate two notifications
    $this->user->notify(new TimetablePublishedNotification('Groupe A1', 'Semaine 42'));
    $this->user->notify(new TimetablePublishedNotification('Groupe A1', 'Semaine 43'));

    $response = $this->actingAs($this->user)
        ->getJson('/notifications');

    $response->assertOk()
        ->assertJsonStructure([
            'notifications' => [
                '*' => ['id', 'data' => ['title', 'message', 'type', 'link'], 'read_at', 'created_at'],
            ],
            'unread_count',
        ])
        ->assertJsonPath('unread_count', 2);

    expect(count($response->json('notifications')))->toBe(2);
});

test('it marks single notification as read', function () {
    $this->user->notify(new TimetablePublishedNotification('Groupe A1', 'Semaine 42'));
    $notification = $this->user->unreadNotifications()->first();

    expect($notification)->not->toBeNull();

    $response = $this->actingAs($this->user)
        ->patchJson("/notifications/{$notification->id}/read");

    $response->assertOk()
        ->assertJson(['marked' => true]);

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

test('it marks all notifications as read', function () {
    $this->user->notify(new TimetablePublishedNotification('Groupe A1', 'Semaine 42'));
    $this->user->notify(new TimetablePublishedNotification('Groupe A1', 'Semaine 43'));

    expect($this->user->unreadNotifications()->count())->toBe(2);

    $response = $this->actingAs($this->user)
        ->postJson('/notifications/read-all');

    $response->assertOk()
        ->assertJson(['count' => 2]);

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

test('it prevents marking another users notification as read', function () {
    $otherUser = User::factory()->student()->create();
    $otherUser->notify(new TimetablePublishedNotification('Groupe B2', 'Semaine 40'));
    $notification = $otherUser->unreadNotifications()->first();

    $response = $this->actingAs($this->user)
        ->patchJson("/notifications/{$notification->id}/read");

    $response->assertOk()
        ->assertJson(['marked' => false]);

    expect($otherUser->unreadNotifications()->count())->toBe(1);
});

test('it dispatches queued timetable published notifications to group students', function () {
    Notification::fake();

    $group = StudentGroup::factory()->create();
    $student1 = User::factory()->student()->create();
    StudentProfile::factory()->create(['user_id' => $student1->id, 'student_group_id' => $group->id]);

    $student2 = User::factory()->student()->create();
    StudentProfile::factory()->create(['user_id' => $student2->id, 'student_group_id' => $group->id]);

    $action = app(NotifyTimetablePublishedAction::class);
    $action->execute([$group->id], 'Semestre 1 - Semaine 40');

    Notification::assertSentTo([$student1, $student2], TimetablePublishedNotification::class);
});

test('it dispatches exam convocation notification when exam is published', function () {
    Notification::fake();
    Queue::fake();

    $coordinator = User::factory()->coordinator()->create();
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $group = StudentGroup::factory()->create();
    StudentProfile::factory()->create(['user_id' => $student->id, 'student_group_id' => $group->id]);

    $period = ExamPeriod::factory()->create([
        'academic_year' => '2026-2027',
        'session_type' => ExamSessionType::Normal,
    ]);
    $program = Program::factory()->create();
    $module = Module::factory()->create(['program_id' => $program->id]);
    $room = Room::factory()->create(['course_capacity' => 40, 'exam_capacity' => 30]);

    $exam = Exam::factory()->create([
        'exam_period_id' => $period->id,
        'module_id' => $module->id,
        'state' => ExamState::Scheduled,
        'starts_at' => now()->addDays(7)->setTime(9, 0),
        'ends_at' => now()->addDays(7)->setTime(11, 0),
    ]);
    $exam->studentGroups()->attach($group->id);

    $assignment = ExamRoomAssignment::query()->create([
        'exam_id' => $exam->id,
        'room_id' => $room->id,
        'position' => 1,
    ]);
    $assignment->invigilators()->create([
        'exam_id' => $exam->id,
        'teacher_id' => $teacher->id,
        'role' => InvigilatorRole::Principal,
    ]);

    ExamCandidate::query()->create([
        'exam_id' => $exam->id,
        'student_id' => $student->id,
        'exam_room_assignment_id' => $assignment->id,
        'seat_number' => 1,
        'convocation_uuid' => (string) Str::uuid(),
    ]);

    app(PublishExamAction::class)->execute($exam, $coordinator);

    Notification::assertSentTo($student, ExamConvocationPublishedNotification::class);
});
