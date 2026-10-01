<?php

namespace App\Models;

use App\Enums\ConflictType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One soft conflict a user knowingly overrode, with their justification (ADR 0002).
 *
 * Audit records are immutable: they can be created, never updated or deleted.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $schedulable_type
 * @property int $schedulable_id
 * @property ConflictType $conflict_type
 * @property string $justification
 * @property array<string, mixed> $details
 * @property Carbon $created_at
 * @property-read User|null $user
 * @property-read Model|null $schedulable
 */
#[Fillable(['user_id', 'schedulable_type', 'schedulable_id', 'conflict_type', 'justification', 'details'])]
class ConflictOverride extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conflict_type' => ConflictType::class,
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Conflict override records are immutable.'));
        static::deleting(fn () => throw new LogicException('Conflict override records are immutable.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }
}
