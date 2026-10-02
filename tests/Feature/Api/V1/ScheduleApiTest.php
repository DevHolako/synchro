<?php

use App\Enums\AccountStatus;
use App\Models\Building;
use App\Models\Campus;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // A Wednesday: current week is 2026-10-05 (Monday) to 2026-10-12 (Monday)
    $this->travelTo('2026-10-07 10:00:00');

    $this->campus = Campus::factory()->create(['name' => 'Campus Casablanca']);
    $this->building = Building::factory()->create(['campus_id' => $this->campus->id]);
    $this->room = Room::factory()->create([
        'building_id' => $this->building->id,
        'name' => 'Room 101',
        'code' => 'R101',
        'course_capacity' => 30,
        'exam_capacity' => 20,
    ]);
    $this->program = Program::factory()->create();
    $this->module = Module::factory()->create([
        'program_id' => $this->program->id,
        'code' => 'INF101',
        'name' => 'Algorithms & Data Structures',
        'color_code' => '#3b82f6',
    ]);
    $this->groupA = StudentGroup::factory()->create([
        'program_id' => $this->program->id,
        'name' => 'Group A',
        'code' => 'GRP-A',
    ]);
    $this->groupB = StudentGroup::factory()->create([
        'program_id' => $this->program->id,
        'name' => 'Group B',
        'code' => 'GRP-B',
    ]);

    $this->teacher = User::factory()->teacher()->create([
        'status' => AccountStatus::Active,
    ]);
    $this->otherTeacher = User::factory()->teacher()->create([
        'status' => AccountStatus::Active,
    ]);

    $this->coordinator = User::factory()->coordinator()->create([
        'status' => AccountStatus::Active,
    ]);

    $this->student = User::factory()->student()->create([
        'status' => AccountStatus::Active,
    ]);
    StudentProfile::factory()->create([
        'user_id' => $this->student->id,
        'student_group_id' => $this->groupA->id,
    ]);
});

test('teacher can retrieve their own schedule via my-schedule with ISO 8601 timestamps', function () {
    $session = CourseSession::factory()
        ->between('2026-10-07 14:00', '2026-10-07 16:00')
        ->forGroups($this->groupA)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

    // Another teacher's session
    CourseSession::factory()
        ->between('2026-10-07 14:00', '2026-10-07 16:00')
        ->forGroups($this->groupB)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->otherTeacher->id,
            'room_id' => $this->room->id,
        ]);

    $token = $this->teacher->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/my-schedule');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'starts_at',
                    'ends_at',
                    'module' => [
                        'id',
                        'code',
                        'name',
                        'color_code',
                    ],
                    'teacher' => [
                        'id',
                        'name',
                    ],
                    'room' => [
                        'id',
                        'name',
                        'code',
                        'building',
                    ],
                    'student_groups',
                    'can_take_attendance',
                    'overrides',
                ],
            ],
        ]);

    $sessionData = $response->json('data.0');
    expect($sessionData['id'])->toBe($session->id);
    expect($sessionData['starts_at'])->toContain('2026-10-07');
    expect($sessionData['module']['code'])->toBe('INF101');
    expect($sessionData['room']['name'])->toBe('Room 101');
    expect($sessionData['teacher']['id'])->toBe($this->teacher->id);
});

test('student can retrieve their own group schedule via my-schedule', function () {
    $groupASession = CourseSession::factory()
        ->between('2026-10-08 10:00', '2026-10-08 12:00')
        ->forGroups($this->groupA)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

    CourseSession::factory()
        ->between('2026-10-08 10:00', '2026-10-08 12:00')
        ->forGroups($this->groupB)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->otherTeacher->id,
            'room_id' => $this->room->id,
        ]);

    $token = $this->student->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/my-schedule');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $groupASession->id);
});

test('coordinator can browse any group schedule via group/{id}', function () {
    $session = CourseSession::factory()
        ->between('2026-10-09 09:00', '2026-10-09 11:00')
        ->forGroups($this->groupB)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

    $token = $this->coordinator->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/group/'.$this->groupB->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $session->id);
});

test('student cannot browse a different group schedule and receives 403 RFC 7807', function () {
    $token = $this->student->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/group/'.$this->groupB->id);

    $response->assertStatus(403)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
        ])
        ->assertJson([
            'status' => 403,
            'title' => 'Forbidden',
        ]);
});

test('student can view their own group schedule via group/{id}', function () {
    $session = CourseSession::factory()
        ->between('2026-10-09 09:00', '2026-10-09 11:00')
        ->forGroups($this->groupA)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

    $token = $this->student->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/group/'.$this->groupA->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $session->id);
});

test('non-existent group returns 404 RFC 7807 problem details', function () {
    $token = $this->coordinator->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/group/999999');

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson([
            'status' => 404,
            'title' => 'Resource Not Found',
        ]);
});

test('schedule endpoints support custom date range filters', function () {
    // Session on Monday Oct 5
    $sessionOct5 = CourseSession::factory()
        ->between('2026-10-05 09:00', '2026-10-05 11:00')
        ->forGroups($this->groupA)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

    // Session next week on Oct 14
    $sessionOct14 = CourseSession::factory()
        ->between('2026-10-14 09:00', '2026-10-14 11:00')
        ->forGroups($this->groupA)
        ->create([
            'module_id' => $this->module->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
        ]);

    $token = $this->student->createToken('mobile')->plainTextToken;

    // Filter only next week: Oct 13 to Oct 16
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/schedules/my-schedule?from=2026-10-13&until=2026-10-16');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $sessionOct14->id);
});
