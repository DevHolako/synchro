<?php

namespace App\Models;

use App\Enums\ExamPeriodStatus;
use App\Enums\ExamSessionType;
use App\Enums\ExamState;
use App\Support\SchoolClock;
use Database\Factories\ExamPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A window of exams: the normal session or the retake session of an academic year.
 *
 * Its status is derived from its dates and exams, never stored (see status()).
 *
 * @property int $id
 * @property string $name
 * @property ExamSessionType $session_type
 * @property string $academic_year
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Exam> $exams
 */
#[Fillable(['name', 'session_type', 'academic_year', 'start_date', 'end_date'])]
class ExamPeriod extends Model
{
    /** @use HasFactory<ExamPeriodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_type' => ExamSessionType::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * How the period appears in a period picker.
     *
     * @return array{id: int, name: string, academic_year: string, session_type: string}
     */
    public function toOption(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'academic_year' => $this->academic_year,
            'session_type' => $this->session_type->value,
        ];
    }

    /**
     * Load what status() needs in the same query as the periods.
     *
     * @param  Builder<ExamPeriod>  $query
     */
    public function scopeWithStatusCounts(Builder $query): void
    {
        $query->withCount(self::statusCounts());
    }

    /**
     * Upcoming, ongoing or ended by the school's calendar; archived once it has exams and all are archived.
     */
    public function status(): ExamPeriodStatus
    {
        if (! array_key_exists('exams_count', $this->attributes)) {
            $this->loadCount(self::statusCounts());
        }

        if ((int) $this->getAttribute('exams_count') > 0 && (int) $this->getAttribute('unarchived_exams_count') === 0) {
            return ExamPeriodStatus::Archived;
        }

        $today = SchoolClock::today()->format('Y-m-d');

        return match (true) {
            $today < $this->start_date->format('Y-m-d') => ExamPeriodStatus::Upcoming,
            $today > $this->end_date->format('Y-m-d') => ExamPeriodStatus::Ended,
            default => ExamPeriodStatus::Ongoing,
        };
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function statusCounts(): array
    {
        return [
            'exams',
            'exams as unarchived_exams_count' => fn (Builder $exams) => $exams->where('state', '!=', ExamState::Archived),
        ];
    }
}
