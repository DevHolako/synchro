<?php

namespace App\Models;

use Database\Factories\CampusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string|null $city
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'code', 'address', 'city', 'is_active'])]
class Campus extends Model
{
    /** @use HasFactory<CampusFactory> */
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
     * @return HasMany<Building, $this>
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    /**
     * @return HasManyThrough<Room, Building, $this>
     */
    public function rooms(): HasManyThrough
    {
        return $this->hasManyThrough(Room::class, Building::class);
    }

    /**
     * Scope a query to only include active campuses.
     *
     * @param  Builder<Campus>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
