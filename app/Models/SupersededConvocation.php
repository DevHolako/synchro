<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A convocation identity an emergency reschedule replaced; its QR code now leads to a warning.
 *
 * @property int $id
 * @property int $exam_id
 * @property int $student_id
 * @property string $convocation_uuid
 * @property int $revision
 * @property Carbon $created_at
 * @property-read Exam $exam
 * @property-read User $student
 */
#[Fillable(['exam_id', 'student_id', 'convocation_uuid', 'revision'])]
class SupersededConvocation extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision' => 'integer',
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
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
