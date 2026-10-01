<?php

namespace App\Models;

use Database\Factories\InvitationTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single-use, expiring Invitation Token. Only the SHA-256 hash of the
 * plain token is persisted; the plain token lives solely in the signed URL.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $invited_by
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $inviter
 */
#[Fillable(['user_id', 'invited_by', 'token_hash', 'expires_at', 'consumed_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
class InvitationToken extends Model
{
    /** @use HasFactory<InvitationTokenFactory> */
    use HasFactory, MassPrunable;

    public const int LIFETIME_HOURS = 72;

    public const int RETENTION_DAYS = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    /**
     * Tokens that can no longer be used and have aged past the retention window.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        return static::query()->where(fn (Builder $query) => $query
            ->where('consumed_at', '<', $cutoff)
            ->orWhere('revoked_at', '<', $cutoff)
            ->orWhere('expires_at', '<', $cutoff));
    }

    /**
     * Scope a query to tokens that have been neither consumed nor revoked.
     *
     * @param  Builder<InvitationToken>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('consumed_at')->whereNull('revoked_at');
    }
}
