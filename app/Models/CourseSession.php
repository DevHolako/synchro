<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CourseSessionFactory;
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
 * A scheduled course slot: one module taught by one teacher in one room to one or more groups.
 *
 * Sessions never cross midnight; they sit inside the 08:00–22:00 grid (ADR 0004).
 *
 * @property int $id
 * @property int $module_id
 * @property int $teacher_id
 * @property int $room_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Module $module
 * @property-read User $teacher
 * @property-read Room $room
 * @property-read Collection<int, StudentGroup> $studentGroups
 * @property-read Collection<int, ConflictOverride> $conflictOverrides
 */
#[Fillable(['module_id', 'teacher_id', 'room_id', 'starts_at', 'ends_at'])]
class CourseSession extends Model
{
    /** @use HasFactory<CourseSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
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
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsToMany<StudentGroup, $this>
     */
    public function studentGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudentGroup::class);
    }

    /**
     * The soft conflicts knowingly overridden when this session was saved (read-only audit).
     *
     * @return MorphMany<ConflictOverride, $this>
     */
    public function conflictOverrides(): MorphMany
    {
        return $this->morphMany(ConflictOverride::class, 'schedulable');
    }

    /**
     * Sessions overlapping the half-open interval [start, end): touching edges do not overlap.
     *
     * Sessions never cross midnight, so only sessions starting the same day can overlap;
     * bounding `starts_at` on both sides keeps the lookup a narrow index range scan.
     *
     * @param  Builder<CourseSession>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->where($query->qualifyColumn('starts_at'), '>=', $start->copy()->startOfDay())
            ->where($query->qualifyColumn('starts_at'), '<', $end)
            ->where($query->qualifyColumn('ends_at'), '>', $start);
    }
}
