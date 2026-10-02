<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One room an exam uses, in the coordinator's order, with its alphabetical range of surnames.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $room_id
 * @property int $position
 * @property int $allocated_students_count
 * @property string|null $first_surname
 * @property string|null $last_surname
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read Room $room
 * @property-read Collection<int, ExamCandidate> $candidates
 * @property-read Collection<int, ExamInvigilator> $invigilators
 */
#[Fillable(['exam_id', 'room_id', 'position', 'allocated_students_count', 'first_surname', 'last_surname'])]
class ExamRoomAssignment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'allocated_students_count' => 'integer',
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
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<ExamCandidate, $this>
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(ExamCandidate::class);
    }

    /**
     * @return HasMany<ExamInvigilator, $this>
     */
    public function invigilators(): HasMany
    {
        return $this->hasMany(ExamInvigilator::class);
    }
}
