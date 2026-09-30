<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $building_id
 * @property string $name
 * @property string|null $code
 * @property int|null $floor
 * @property int $course_capacity
 * @property int $exam_capacity
 * @property bool $has_projector
 * @property bool $is_lab
 * @property bool $has_computers
 * @property bool $has_sound_system
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Building $building
 */
#[Fillable([
    'building_id',
    'name',
    'code',
    'floor',
    'course_capacity',
    'exam_capacity',
    'has_projector',
    'is_lab',
    'has_computers',
    'has_sound_system',
    'is_active',
])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'course_capacity' => 'integer',
            'exam_capacity' => 'integer',
            'has_projector' => 'boolean',
            'is_lab' => 'boolean',
            'has_computers' => 'boolean',
            'has_sound_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Scope a query to only include active rooms.
     *
     * @param  Builder<Room>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
