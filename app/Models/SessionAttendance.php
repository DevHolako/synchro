<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\SessionAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One student's mark on one course session's attendance register (Part 03 / Ticket 04).
 *
 * @property int $id
 * @property int $course_session_id
 * @property int $student_id
 * @property AttendanceStatus $status
 * @property string|null $remarks
 * @property int $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CourseSession $courseSession
 * @property-read User $student
 */
#[Fillable(['course_session_id', 'student_id', 'status', 'remarks', 'recorded_by'])]
class SessionAttendance extends Model
{
    /** @use HasFactory<SessionAttendanceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    /**
     * @return BelongsTo<CourseSession, $this>
     */
    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
