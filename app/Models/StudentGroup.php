<?php

namespace App\Models;

use Database\Factories\StudentGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $program_id
 * @property int|null $campus_id
 * @property string $name
 * @property string|null $code
 * @property string $academic_year
 * @property int $expected_headcount
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Program $program
 * @property-read Campus|null $campus
 */
#[Fillable([
    'program_id',
    'campus_id',
    'name',
    'code',
    'academic_year',
    'expected_headcount',
    'is_active',
])]
class StudentGroup extends Model
{
    /** @use HasFactory<StudentGroupFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_headcount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Scope a query to only include active student groups.
     *
     * @param  Builder<StudentGroup>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to a specific academic year.
     *
     * @param  Builder<StudentGroup>  $query
     */
    public function scopeForYear(Builder $query, string $year): void
    {
        $query->where('academic_year', $year);
    }
}
