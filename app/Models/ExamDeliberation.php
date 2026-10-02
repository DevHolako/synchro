<?php

namespace App\Models;

use App\Enums\GradeSheetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An exam's grade sheet and its deliberation (spec 05, ADR 0006): drafted by the module
 * teacher, submitted, then locked by a coordinator (ticket 03).
 *
 * @property int $id
 * @property int $exam_id
 * @property GradeSheetStatus $status
 * @property Carbon|null $submitted_at
 * @property int|null $submitted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read User|null $submitter
 */
#[Fillable(['exam_id', 'status', 'submitted_at', 'submitted_by'])]
class ExamDeliberation extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GradeSheetStatus::class,
            'submitted_at' => 'datetime',
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
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Lock this sheet's row for the rest of the transaction, after its exam's (`Exam::lockRow()`).
     */
    public function lockRow(): self
    {
        return static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
    }
}
