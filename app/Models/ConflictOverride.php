<?php

namespace App\Models;

use App\Enums\ConflictType;
use App\Models\Builders\ImmutableBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One soft conflict a user knowingly overrode, with their justification (ADR 0002).
 *
 * Audit records are immutable: they can be created, never updated or deleted, whether
 * through a model or a bulk query. Their author cannot be deleted either.
 *
 * @property int $id
 * @property int $user_id
 * @property string $schedulable_type
 * @property int $schedulable_id
 * @property ConflictType $conflict_type
 * @property string $justification
 * @property array<string, mixed> $details
 * @property Carbon $created_at
 * @property-read User $user
 * @property-read Model|null $schedulable
 */
#[Fillable(['user_id', 'schedulable_type', 'schedulable_id', 'conflict_type', 'justification', 'details'])]
#[UseEloquentBuilder(ImmutableBuilder::class)]
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
