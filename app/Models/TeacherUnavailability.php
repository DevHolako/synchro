<?php

namespace App\Models;

use App\Enums\UnavailabilityStatus;
use App\Enums\UnavailabilityType;
use Database\Factories\TeacherUnavailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A teacher's declared unavailability: a recurring weekly block or an ad-hoc date range.
 *
 * Pending and approved unavailabilities are soft scheduling constraints (ADR 0002).
 *
 * @property int $id
 * @property int $teacher_id
 * @property UnavailabilityType $type
 * @property int|null $day_of_week ISO-8601 weekday, 1 = Monday … 7 = Sunday
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string|null $start_time H:i
 * @property string|null $end_time H:i
 * @property string $reason
 * @property UnavailabilityStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $teacher
 * @property-read User|null $reviewer
 */
#[Fillable([
    'teacher_id', 'type', 'day_of_week', 'start_date', 'end_date', 'start_time', 'end_time',
    'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
])]
class TeacherUnavailability extends Model
{
    /** @use HasFactory<TeacherUnavailabilityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => UnavailabilityType::class,
            'status' => UnavailabilityStatus::class,
            'day_of_week' => 'integer',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === UnavailabilityStatus::Pending;
    }

    /**
     * Unavailabilities that still constrain scheduling: pending or approved.
     *
     * @param  Builder<TeacherUnavailability>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', UnavailabilityStatus::active());
    }

    /**
     * Active unavailabilities of the same teacher and type that overlap the candidate.
     *
     * Dates are inclusive whole days; times are half-open, so 10:00–12:00 and 12:00–14:00
     * do not overlap. An ad-hoc range without times covers whole days. An open-ended
     * recurring block (no end date) runs forever.
     *
     * @param  Builder<TeacherUnavailability>  $query
     */
    public function scopeOverlapping(Builder $query, self $candidate): void
    {
        $startDate = $candidate->getAttributes()['start_date'];
        $endDate = $candidate->getAttributes()['end_date'] ?? null;
        $startTime = $candidate->getAttributes()['start_time'] ?? null;
        $endTime = $candidate->getAttributes()['end_time'] ?? null;

        $query->active()
            ->where('teacher_id', $candidate->teacher_id)
            ->where('type', $candidate->type)
            ->when($candidate->exists, fn (Builder $query) => $query->whereKeyNot($candidate->getKey()))
            ->when($endDate !== null, fn (Builder $query) => $query->where('start_date', '<=', $endDate))
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $startDate))
            ->when(
                $candidate->type === UnavailabilityType::RecurringWeekly,
                fn (Builder $query) => $query->where('day_of_week', $candidate->day_of_week),
            )
            ->when($startTime !== null, fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereNull('start_time')->orWhere(
                    fn (Builder $query) => $query->where('start_time', '<', $endTime)->where('end_time', '>', $startTime),
                ),
            ));
    }

    /**
     * Times are stored as H:i:s so they compare correctly in SQL, and exposed as H:i.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function startTime(): Attribute
    {
        return $this->timeAttribute();
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function endTime(): Attribute
    {
        return $this->timeAttribute();
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    private function timeAttribute(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : substr($value, 0, 5),
            set: fn (?string $value) => $value === null ? null : substr($value.':00', 0, 8),
        );
    }
}
