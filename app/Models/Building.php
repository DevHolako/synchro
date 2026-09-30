<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campus_id
 * @property string $name
 * @property string|null $code
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Campus $campus
 */
#[Fillable(['campus_id', 'name', 'code', 'is_active'])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Scope a query to only include active buildings.
     *
     * @param  Builder<Building>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
