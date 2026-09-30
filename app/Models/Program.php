<?php

namespace App\Models;

use App\Enums\ProgramModality;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string $code
 * @property ProgramModality $program_modality
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department $department
 */
#[Fillable(['department_id', 'name', 'code', 'program_modality', 'description', 'is_active'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'program_modality' => ProgramModality::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<StudentGroup, $this>
     */
    public function studentGroups(): HasMany
    {
        return $this->hasMany(StudentGroup::class);
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Scope a query to only include active programs.
     *
     * @param  Builder<Program>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to programs with Temps Aménagé modality.
     *
     * @param  Builder<Program>  $query
     */
    public function scopeTempsAmenage(Builder $query): void
    {
        $query->where('program_modality', ProgramModality::TempsAmenage);
    }

    /**
     * Scope a query to programs with Formation Initiale modality.
     *
     * @param  Builder<Program>  $query
     */
    public function scopeFormationInitiale(Builder $query): void
    {
        $query->where('program_modality', ProgramModality::FormationInitiale);
    }
}
