<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $program_id
 * @property int|null $teacher_id
 * @property string $name
 * @property string $code
 * @property int $total_hours
 * @property int $lecture_hours
 * @property int $tp_hours
 * @property string $color_code
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Program $program
 * @property-read User|null $teacher
 */
#[Fillable([
    'program_id',
    'teacher_id',
    'name',
    'code',
    'total_hours',
    'lecture_hours',
    'tp_hours',
    'color_code',
    'description',
    'is_active',
])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_hours' => 'integer',
            'lecture_hours' => 'integer',
            'tp_hours' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * How the module is named on screens and documents: code, then name.
     */
    public function label(): string
    {
        return "{$this->code} · {$this->name}";
    }

    /**
     * Scope a query to only include active modules.
     *
     * @param  Builder<Module>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to a specific program.
     *
     * @param  Builder<Module>  $query
     */
    public function scopeForProgram(Builder $query, int $programId): void
    {
        $query->where('program_id', $programId);
    }
}
