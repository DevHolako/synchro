<?php

namespace App\Models;

use App\Enums\ExamState;
use App\Enums\Permission;
use App\Models\Concerns\OverlapsInTime;
use App\Support\SchoolClock;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamPeriod $examPeriod
 * @property-read Module $module
 * @property-read User|null $publisher
 * @property-read Collection<int, StudentGroup> $studentGroups
 * @property-read Collection<int, ConflictOverride> $conflictOverrides
 */
#[Fillable(['exam_period_id', 'module_id', 'starts_at', 'ends_at', 'state', 'published_at', 'published_by'])]
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
     * Whether the exam has begun, by the school's clock.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at->lessThanOrEqualTo(SchoolClock::now());
    }

    /**
     * Still a draft or scheduled once its start has passed: it can no longer be published.
     */
    public function isOverdue(): bool
    {
        return $this->state->isEditable() && $this->hasStarted();
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
     * Published exams and their history that concern a user: their group's, or those of the
     * modules they teach.
     *
     * @param  Builder<Exam>  $query
     */
    public function scopeConcerning(Builder $query, User $user): void
    {
        $groupId = $user->studentProfile?->student_group_id;

        $query->whereIn($query->qualifyColumn('state'), ExamState::visibleToCandidates())
            ->where(fn (Builder $concerned) => $concerned
                ->whereHas('module', fn (Builder $modules) => $modules->where('teacher_id', $user->id))
                ->when($groupId, fn (Builder $query, int $groupId) => $query
                    ->orWhereHas('studentGroups', fn (Builder $groups) => $groups->whereKey($groupId))));
    }
}
