<?php

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\AssignInvigilatorsAction;
use App\Actions\Exams\RenderConvocationPdfAction;
use App\Enums\AccountStatus;
use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\SupersededConvocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-10-05 08:00:00');
    config(['app.schedule_timezone' => 'UTC']);
    Storage::fake('local');

    $this->coordinator = User::factory()->coordinator()->create([
        'status' => AccountStatus::Active,
    ]);
    [$this->leadA, $this->leadB] = User::factory()->teacher()->count(2)->create([
        'status' => AccountStatus::Active,
    ])->all();

    $program = Program::factory()->create();
    $group = StudentGroup::factory()->create(['program_id' => $program->id]);

    $studentProfileA = StudentProfile::factory()->create([
        'student_group_id' => $group->id,
        'last_name' => 'Alami',
    ]);
    $this->studentA = $studentProfileA->user;
    $this->studentA->update(['status' => AccountStatus::Active]);

    $studentProfileB = StudentProfile::factory()->create([
        'student_group_id' => $group->id,
        'last_name' => 'Zerouali',
    ]);
    $this->studentB = $studentProfileB->user;
    $this->studentB->update(['status' => AccountStatus::Active]);

    $this->module = Module::factory()->create([
        'program_id' => $program->id,
        'code' => 'ALG201',
        'name' => 'Advanced Algorithms',
        'color_code' => '#10b981',
    ]);

    $this->period = ExamPeriod::factory()->between('2026-10-01', '2026-10-31')->create();

    $this->exam = Exam::factory()->between('2026-10-12 09:00', '2026-10-12 11:00')->forGroups($group)->create([
        'exam_period_id' => $this->period->id,
        'module_id' => $this->module->id,
    ]);

    app(AllocateExamRoomsAction::class)->execute($this->exam, [
        Room::factory()->create(['name' => 'Amphi A', 'code' => 'AMP-A', 'exam_capacity' => 1])->id,
        Room::factory()->create(['name' => 'Salle B', 'code' => 'SAL-B', 'exam_capacity' => 1])->id,
    ]);

    [$this->roomA, $this->roomB] = $this->exam->roomAssignments()->get()->all();
    app(AssignInvigilatorsAction::class)->execute($this->roomA, $this->leadA->id, []);
    app(AssignInvigilatorsAction::class)->execute($this->roomB, $this->leadB->id, []);

    $this->exam->update(['state' => ExamState::Published]);

    $this->candidateA = ExamCandidate::query()->where('exam_room_assignment_id', $this->roomA->id)->sole();
    $this->candidateB = ExamCandidate::query()->where('exam_room_assignment_id', $this->roomB->id)->sole();

    // Move clock into check-in window (exam starts at 09:00, check-in opens at 08:00)
    $this->travelTo('2026-10-12 08:30:00');
});

test('student can retrieve their published exams and split room allocation via my-exams', function () {
    $token = $this->studentA->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/exams/my-exams');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'starts_at',
                    'ends_at',
                    'state',
                    'period' => ['id', 'name', 'session_type'],
                    'module' => ['id', 'code', 'name', 'color_code'],
                    'my_seat' => [
                        'candidate_id',
                        'room',
                        'room_code',
                        'seat_number',
                        'convocation_uuid',
                        'convocation_status',
                        'checked_in_at',
                    ],
                    'assigned_rooms' => [
                        '*' => ['id', 'name', 'code', 'building', 'first_surname', 'last_surname', 'allocated_students_count'],
                    ],
                ],
            ],
        ]);

    $examPayload = $response->json('data.0');
    expect($examPayload['id'])->toBe($this->exam->id);
    expect($examPayload['my_seat']['room'])->toBe('Amphi A');
    expect($examPayload['my_seat']['convocation_uuid'])->toBe($this->candidateA->convocation_uuid);
    expect(count($examPayload['assigned_rooms']))->toBe(2);
});

test('student can download their convocation PDF when ready, or receives 409 when pending', function () {
    $token = $this->studentA->createToken('mobile')->plainTextToken;

    // 1. When file does not exist yet -> 409 Document Pending
    $pendingResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/exams/convocation/'.$this->exam->id.'/download');

    $pendingResponse->assertStatus(409)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson([
            'status' => 409,
            'title' => 'Document Pending',
        ]);

    // 2. Put file in storage -> 200 Streamed download
    Storage::disk('local')->put($this->candidateA->convocationPath(), '%PDF-1.4 Mock Convocation');

    $readyResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->get('/api/v1/exams/convocation/'.$this->exam->id.'/download');

    $readyResponse->assertOk()
        ->assertHeader('content-disposition');
});

test('invigilator can scan candidate QR code and mark check-in successfully', function () {
    $token = $this->leadA->createToken('mobile-scanner')->plainTextToken;

    expect($this->candidateA->checked_in_at)->toBeNull();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/scan', [
            'uuid' => $this->candidateA->convocation_uuid,
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'candidate' => [
                'id' => $this->candidateA->id,
                'room' => 'Amphi A',
                'seat_number' => $this->candidateA->seat_number,
            ],
        ]);

    $this->candidateA->refresh();
    expect($this->candidateA->checked_in_at)->not->toBeNull();
    expect($this->candidateA->checked_in_by)->toBe($this->leadA->id);
});

test('invigilator scanning full signed verification URL extracts UUID and succeeds', function () {
    $token = $this->leadA->createToken('mobile-scanner')->plainTextToken;
    $url = app(RenderConvocationPdfAction::class)->verificationUrl($this->candidateA);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/scan', [
            'token' => $url,
        ]);

    $response->assertOk()
        ->assertJson(['status' => 'success']);

    $this->candidateA->refresh();
    expect($this->candidateA->checked_in_at)->not->toBeNull();
});

test('scanning candidate assigned to a different room returns 422 with status wrong_room', function () {
    // leadA is in Amphi A, candidateB is seated in Salle B
    $token = $this->leadA->createToken('mobile-scanner')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/scan', [
            'uuid' => $this->candidateB->convocation_uuid,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'wrong_room',
            'assigned_room' => 'Salle B',
            'candidate' => [
                'id' => $this->candidateB->id,
                'room' => 'Salle B',
            ],
        ]);

    $this->candidateB->refresh();
    expect($this->candidateB->checked_in_at)->toBeNull();
});

test('scanning already checked-in candidate returns 422 with status already_checked_in', function () {
    $this->candidateA->update(['checked_in_at' => now(), 'checked_in_by' => $this->leadA->id]);

    $token = $this->leadA->createToken('mobile-scanner')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/scan', [
            'uuid' => $this->candidateA->convocation_uuid,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'already_checked_in',
            'candidate' => [
                'id' => $this->candidateA->id,
            ],
        ]);
});

test('scanning superseded convocation returns 409 with status superseded', function () {
    $superseded = SupersededConvocation::create([
        'exam_id' => $this->exam->id,
        'student_id' => $this->studentA->id,
        'convocation_uuid' => (string) Str::uuid(),
        'revision' => 1,
    ]);

    $token = $this->leadA->createToken('mobile-scanner')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/scan', [
            'uuid' => $superseded->convocation_uuid,
        ]);

    $response->assertStatus(409)
        ->assertJson([
            'status' => 'superseded',
            'candidate' => [
                'current_room' => 'Amphi A',
            ],
        ]);
});

test('scanning non-existent UUID returns 404 RFC 7807 problem details', function () {
    $token = $this->leadA->createToken('mobile-scanner')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/scan', [
            'uuid' => '00000000-0000-0000-0000-000000000000',
        ]);

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson([
            'status' => 404,
            'title' => 'Resource Not Found',
        ]);
});

test('student without check-in permission receives 403 RFC 7807 problem details', function () {
    $studentToken = $this->studentA->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$studentToken)
        ->postJson('/api/v1/check-in/scan', [
            'uuid' => $this->candidateA->convocation_uuid,
        ]);

    $response->assertStatus(403)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson([
            'status' => 403,
            'title' => 'Forbidden',
        ]);
});
