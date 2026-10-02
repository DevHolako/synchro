<?php

namespace App\Models;

use App\Models\Builders\ImmutableBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One emergency reschedule of a published exam, with its reason (ADR 0005). Append-only.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $user_id
 * @property int $revision
 * @property string $reason
 * @property Carbon $previous_starts_at
 * @property Carbon $previous_ends_at
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property list<int> $previous_room_ids
 * @property list<int> $room_ids
 * @property list<int> $released_invigilator_ids
 * @property Carbon $created_at
 * @property-read Exam $exam
 * @property-read User $user
 */
#[Fillable([
    'exam_id', 'user_id', 'revision', 'reason', 'previous_starts_at', 'previous_ends_at', 'starts_at', 'ends_at',
    'previous_room_ids', 'room_ids', 'released_invigilator_ids',
])]
#[UseEloquentBuilder(ImmutableBuilder::class)]
class ExamReschedule extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'previous_starts_at' => 'datetime',
            'previous_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'previous_room_ids' => 'array',
            'room_ids' => 'array',
            'released_invigilator_ids' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
