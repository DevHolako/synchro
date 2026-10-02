<?php

namespace App\Models;

use App\Enums\GradeSheetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One candidate's line on an exam's grade sheet: continuous assessment (CC) and exam grades out
 * of 20, and the final grade the server computes from the module's weighting. Lines of a
 * locked deliberation cannot be changed or deleted through the model.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $student_id
 * @property string|null $continuous_assessment_grade
 * @property string|null $exam_grade
 * @property string|null $final_grade
 * @property string|null $previous_final_grade On a retake line, the normal session's locked final.
 * @property bool $is_absent
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read User $student
 */
#[Fillable(['exam_id', 'student_id', 'continuous_assessment_grade', 'exam_grade', 'final_grade', 'previous_final_grade', 'is_absent', 'remarks'])]
class ExamGrade extends Model
{
    /** The lowest passing final grade, out of 20 (spec 05: below it, the student sits the retake). */
    public const string PASS_MARK = '10.00';

    /** The highest grade, out of 20. */
    public const int MAX_GRADE = 20;

    protected static function booted(): void
    {
        $refuseWhenLocked = function (ExamGrade $grade): void {
            if (ExamDeliberation::query()->where('exam_id', $grade->exam_id)->where('status', GradeSheetStatus::Locked)->exists()) {
                throw new LogicException('Grades of a locked deliberation cannot be changed.');
            }
        };

        static::updating($refuseWhenLocked);
        static::deleting($refuseWhenLocked);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'continuous_assessment_grade' => 'decimal:2',
            'exam_grade' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'previous_final_grade' => 'decimal:2',
            'is_absent' => 'boolean',
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
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
