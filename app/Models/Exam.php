<?php

namespace App\Models;

use App\Enums\BookingType;
use App\Enums\ExamSessionType;
use App\Enums\ExamState;
use App\Enums\InvigilatorRole;
use App\Enums\Permission;
use App\Models\Concerns\OverlapsInTime;
use App\Services\Scheduling\BookingSlot;
use App\Support\SchoolClock;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * One module's exam for one or more groups, inside an exam period (ADR 0005 lifecycle).
 *
 * Like course sessions, exams sit on one day inside the 08:00–22:00 grid (ADR 0004), stored as
 * the school's wall-clock time.
 *
 * @property int $id
 * @property int $exam_period_id
 * @property int $module_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property ExamState $state
 * @property bool $force_single_room
 * @property int $revision
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamPeriod $examPeriod
 * @property-read Module $module
 * @property-read User|null $publisher
 * @property-read Collection<int, StudentGroup> $studentGroups
 * @property-read Collection<int, ConflictOverride> $conflictOverrides
 * @property-read Collection<int, ExamRoomAssignment> $roomAssignments
 * @property-read Collection<int, ExamCandidate> $candidates
 * @property-read Collection<int, ExamInvigilator> $invigilators
 * @property-read Collection<int, ExamReschedule> $reschedules
 * @property-read ExamDeliberation|null $deliberation
 * @property-read Collection<int, ExamGrade> $grades
 */
#[Fillable(['exam_period_id', 'module_id', 'starts_at', 'ends_at', 'state', 'force_single_room', 'revision', 'published_at', 'published_by'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    use OverlapsInTime;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'state' => 'draft',
        'force_single_room' => false,
        'revision' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'state' => ExamState::class,
            'force_single_room' => 'boolean',
            'revision' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExamPeriod, $this>
     */
    public function examPeriod(): BelongsTo
    {
        return $this->belongsTo(ExamPeriod::class);
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @return BelongsToMany<StudentGroup, $this>
     */
    public function studentGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudentGroup::class);
    }

    /**
     * The soft conflicts knowingly overridden for this exam (read-only audit).
     *
     * @return MorphMany<ConflictOverride, $this>
     */
    public function conflictOverrides(): MorphMany
    {
        return $this->morphMany(ConflictOverride::class, 'schedulable');
    }

    /**
     * The rooms the exam is split across, in the coordinator's order.
     *
     * @return HasMany<ExamRoomAssignment, $this>
     */
    public function roomAssignments(): HasMany
    {
        return $this->hasMany(ExamRoomAssignment::class)->orderBy('position');
    }

    /**
     * @return HasMany<ExamCandidate, $this>
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(ExamCandidate::class);
    }

    /**
     * @return HasMany<ExamInvigilator, $this>
     */
    public function invigilators(): HasMany
    {
        return $this->hasMany(ExamInvigilator::class);
    }

    /**
     * The exam's emergency reschedules, latest first.
     *
     * @return HasMany<ExamReschedule, $this>
     */
    public function reschedules(): HasMany
    {
        return $this->hasMany(ExamReschedule::class)->orderByDesc('revision');
    }

    /**
     * The exam's grade sheet and deliberation, once the sheet has been opened.
     *
     * @return HasOne<ExamDeliberation, $this>
     */
    public function deliberation(): HasOne
    {
        return $this->hasOne(ExamDeliberation::class);
    }

    /**
     * @return HasMany<ExamGrade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(ExamGrade::class);
    }

    /**
     * What the exam books, as the conflict detector reads it: its groups, rooms and invigilators.
     */
    public function bookingSlot(): BookingSlot
    {
        return new BookingSlot(
            type: BookingType::Exam,
            teacherIds: array_values(array_map('intval', $this->invigilators()->pluck('teacher_id')->all())),
            roomIds: array_values(array_map('intval', $this->roomAssignments()->pluck('room_id')->all())),
            groupIds: array_values(array_map('intval', $this->studentGroups()->pluck('student_groups.id')->all())),
            startsAt: $this->starts_at->toImmutable(),
            endsAt: $this->ends_at->toImmutable(),
            ignoreId: $this->id,
        );
    }

    /**
     * Where the exam's door lists and attendance sheets are stored (private disk).
     */
    public function rosterPath(): string
    {
        return "exam-rosters/{$this->id}.pdf";
    }

    /**
     * Lock this exam's row for the rest of the transaction. Every exam write takes it first
     * (after its period, before rooms, users and groups), so staffing, scheduling and publishing
     * one exam queue up instead of interleaving or deadlocking.
     */
    public function lockRow(): void
    {
        static::query()->whereKey($this->id)->lockForUpdate()->first();
    }

    /**
     * The room this user invigilates in the exam, if any.
     */
    public function invigilatedAssignmentId(User $user): ?int
    {
        $roomId = $this->invigilators()->where('teacher_id', $user->id)->value('exam_room_assignment_id');

        return $roomId === null ? null : (int) $roomId;
    }

    /** Candidates may be checked in from this many minutes before the start. */
    public const int CHECK_IN_OPENS_MINUTES_BEFORE = 60;

    /**
     * Whether candidates may be checked in now: a published exam, from an hour before its start
     * until its end, by the school's clock.
     */
    public function isCheckInOpen(): bool
    {
        $now = SchoolClock::now();

        return $this->state === ExamState::Published
            && $now->greaterThanOrEqualTo($this->starts_at->copy()->subMinutes(self::CHECK_IN_OPENS_MINUTES_BEFORE))
            && $now->lessThan($this->ends_at);
    }

    /**
     * The exam's window for these invigilators alone, to check them against their other bookings.
     *
     * @param  list<int>  $teacherIds
     */
    public function invigilationSlot(array $teacherIds): BookingSlot
    {
        return new BookingSlot(
            type: BookingType::Exam,
            teacherIds: $teacherIds,
            roomIds: [],
            groupIds: [],
            startsAt: $this->starts_at->toImmutable(),
            endsAt: $this->ends_at->toImmutable(),
            ignoreId: $this->id,
        );
    }

    /**
     * Whether the exam has begun, by the school's clock.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at->lessThanOrEqualTo(SchoolClock::now());
    }

    /**
     * Whether the exam's grade sheet can be opened: the exam is over.
     */
    public function isGradable(): bool
    {
        return $this->state->isFinished();
    }

    /**
     * Whether the exam belongs to a retake session (rattrapage): only the students who failed the
     * module sit it, and they keep their continuous assessment and their better final.
     */
    public function isRetake(): bool
    {
        return $this->examPeriod->session_type === ExamSessionType::Rattrapage;
    }

    /**
     * Still a draft or scheduled once its start has passed: it can no longer be published.
     */
    public function isOverdue(): bool
    {
        return $this->state->isEditable() && $this->hasStarted();
    }

    /**
     * Exams with a room that has no lead invigilator yet.
     *
     * @param  Builder<Exam>  $query
     */
    public function scopeMissingLead(Builder $query): void
    {
        $query->whereHas('roomAssignments', self::roomWithoutLead(...));
    }

    /**
     * Exams whose every room has a lead invigilator.
     *
     * @param  Builder<Exam>  $query
     */
    public function scopeStaffed(Builder $query): void
    {
        $query->whereDoesntHave('roomAssignments', self::roomWithoutLead(...));
    }

    /**
     * @param  Builder<ExamRoomAssignment>  $rooms
     */
    private static function roomWithoutLead(Builder $rooms): void
    {
        $rooms->whereDoesntHave('invigilators', fn (Builder $invigilators) => $invigilators->where('role', InvigilatorRole::Principal));
    }

    /**
     * The exams a user may see: every state for exam managers, otherwise the ones concerning them.
     *
     * @param  Builder<Exam>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasPermission(Permission::ManageExams)) {
            return;
        }

        if (! $user->hasPermission(Permission::ViewExams)) {
            $query->whereKey([]);

            return;
        }

        $query->concerning($user);
    }

    /**
     * Published exams and their history that concern a user: their group's, those of the
     * modules they teach, or those they invigilate.
     *
     * @param  Builder<Exam>  $query
     */
    public function scopeConcerning(Builder $query, User $user): void
    {
        $groupId = $user->studentProfile?->student_group_id;

        $query->whereIn($query->qualifyColumn('state'), ExamState::visibleToCandidates())
            ->where(fn (Builder $concerned) => $concerned
                ->whereHas('module', fn (Builder $modules) => $modules->where('teacher_id', $user->id))
                ->orWhereHas('invigilators', fn (Builder $invigilators) => $invigilators->where('teacher_id', $user->id))
                ->when($groupId, fn (Builder $query, int $groupId) => $query
                    ->orWhereHas('studentGroups', fn (Builder $groups) => $groups->whereKey($groupId))));
    }
}
