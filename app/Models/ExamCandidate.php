<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A student sitting an exam: their room and seat.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $student_id
 * @property int $exam_room_assignment_id
 * @property int $seat_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read User $student
 * @property-read ExamRoomAssignment $roomAssignment
 */
#[Fillable(['exam_id', 'student_id', 'exam_room_assignment_id', 'seat_number'])]
class ExamCandidate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['seat_number' => 'integer'];
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
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<ExamRoomAssignment, $this>
     */
    public function roomAssignment(): BelongsTo
    {
        return $this->belongsTo(ExamRoomAssignment::class, 'exam_room_assignment_id');
    }
}
