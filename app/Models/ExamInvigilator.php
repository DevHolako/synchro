<?php

namespace App\Models;

use App\Enums\InvigilatorRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A teacher watching one room of an exam, as its lead or as an assistant.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $exam_room_assignment_id
 * @property int $teacher_id
 * @property InvigilatorRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamRoomAssignment $roomAssignment
 * @property-read User $teacher
 */
#[Fillable(['exam_id', 'exam_room_assignment_id', 'teacher_id', 'role'])]
class ExamInvigilator extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => InvigilatorRole::class];
    }

    /**
     * @return BelongsTo<ExamRoomAssignment, $this>
     */
    public function roomAssignment(): BelongsTo
    {
        return $this->belongsTo(ExamRoomAssignment::class, 'exam_room_assignment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
