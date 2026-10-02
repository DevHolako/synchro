<?php

use App\Enums\BookingType;
use App\Enums\ConflictType;
use App\Models\CourseSession;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectorService;
use App\Services\Scheduling\SessionSlot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->room = Room::factory()->create(['name' => 'Amphi A']);
    $this->teacher = User::factory()->teacher()->create(['name' => 'Pr. Alaoui']);
    $this->group = StudentGroup::factory()->create(['name' => '1CI-A']);

    $this->existing = CourseSession::factory()
        ->between('2026-10-12 10:00', '2026-10-12 12:00')
        ->create(['room_id' => $this->room->id, 'teacher_id' => $this->teacher->id]);
    $this->existing->studentGroups()->attach($this->group);

    $this->detector = app(ConflictDetectorService::class);
});

function slotAt(string $start, string $end, array $overrides = []): SessionSlot
{
    return new SessionSlot(
        type: BookingType::CourseSession,
        teacherIds: [$overrides['teacher'] ?? User::factory()->teacher()->create()->id],
        roomIds: [$overrides['room'] ?? Room::factory()->create()->id],
        groupIds: $overrides['groups'] ?? [StudentGroup::factory()->create()->id],
        startsAt: CarbonImmutable::parse($start),
        endsAt: CarbonImmutable::parse($end),
        ignoreId: $overrides['ignore'] ?? null,
    );
}

test('adjacent slots do not conflict but a one-minute overlap does', function () {
    $adjacent = $this->detector->checkConflicts(slotAt('2026-10-12 12:00', '2026-10-12 14:00', ['room' => $this->room->id]));
    $overlap = $this->detector->checkConflicts(slotAt('2026-10-12 11:59', '2026-10-12 13:00', ['room' => $this->room->id]));
    $before = $this->detector->checkConflicts(slotAt('2026-10-12 08:00', '2026-10-12 10:00', ['room' => $this->room->id]));

    expect($adjacent->hasHardConflicts())->toBeFalse()
        ->and($before->hasHardConflicts())->toBeFalse()
        ->and($overlap->hasHardConflicts())->toBeTrue();
});

test('each resource collision is reported with its type and name', function (string $resource, ConflictType $type, string $name) {
    $overrides = match ($resource) {
        'room' => ['room' => $this->room->id],
        'teacher' => ['teacher' => $this->teacher->id],
        'group' => ['groups' => [$this->group->id]],
    };

    $result = $this->detector->checkConflicts(slotAt('2026-10-12 11:00', '2026-10-12 13:00', $overrides));

    expect($result->hardConflicts)->toHaveCount(1)
        ->and($result->hardConflicts[0]->type)->toBe($type)
        ->and($result->hardConflicts[0]->resourceName)->toBe($name)
        ->and($result->hardConflicts[0]->bookingId)->toBe($this->existing->id)
        ->and($result->hardConflicts[0]->toArray()['starts_at'])->toBe('2026-10-12 10:00');
})->with([
    'room' => ['room', ConflictType::Room, 'Amphi A'],
    'teacher' => ['teacher', ConflictType::Teacher, 'Pr. Alaoui'],
    'group' => ['group', ConflictType::Group, '1CI-A'],
]);

test('a slot double-booking every resource reports all of them', function () {
    $result = $this->detector->checkConflicts(slotAt('2026-10-12 09:00', '2026-10-12 10:30', [
        'room' => $this->room->id,
        'teacher' => $this->teacher->id,
        'groups' => [$this->group->id],
    ]));

    expect(array_map(fn ($conflict) => $conflict->type, $result->hardConflicts))
        ->toEqualCanonicalizing([ConflictType::Room, ConflictType::Teacher, ConflictType::Group]);
});

test('a shared session conflicts for every group it serves', function () {
    $other = StudentGroup::factory()->create(['name' => '1CI-B']);
    $this->existing->studentGroups()->attach($other);

    $result = $this->detector->checkConflicts(slotAt('2026-10-12 11:00', '2026-10-12 12:00', [
        'groups' => [$this->group->id, $other->id, StudentGroup::factory()->create()->id],
    ]));

    expect(array_map(fn ($conflict) => $conflict->resourceName, $result->hardConflicts))
        ->toEqualCanonicalizing(['1CI-A', '1CI-B']);
});

test('sessions on other days never conflict', function () {
    $result = $this->detector->checkConflicts(slotAt('2026-10-13 10:00', '2026-10-13 12:00', [
        'room' => $this->room->id,
        'teacher' => $this->teacher->id,
        'groups' => [$this->group->id],
    ]));

    expect($result->hasHardConflicts())->toBeFalse();
});

test('a session being edited does not conflict with itself', function () {
    $result = $this->detector->checkConflicts(slotAt('2026-10-12 10:30', '2026-10-12 12:30', [
        'room' => $this->room->id,
        'teacher' => $this->teacher->id,
        'groups' => [$this->group->id],
        'ignore' => $this->existing->id,
    ]));

    expect($result->hasHardConflicts())->toBeFalse();
});

test('a check runs one query per resource type and source, and per soft rule', function () {
    $slot = slotAt('2026-10-12 11:00', '2026-10-12 13:00', ['room' => $this->room->id]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->detector->checkConflicts($slot);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Sessions and exams: room, teacher, groups each. Soft rules: capacity, unavailability.
    expect($queries)->toHaveCount(8);
});

test('the overlap lookups are backed by composite indexes', function () {
    $columns = fn (string $table) => collect(Schema::getIndexes($table))->pluck('columns')->all();

    expect($columns('course_sessions'))->toContain(['room_id', 'starts_at'], ['teacher_id', 'starts_at'])
        ->and($columns('course_session_student_group'))->toContain(['student_group_id']);
});
