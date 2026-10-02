<?php

namespace App\Models;

use App\Enums\GradeSheetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * An exam's grade sheet and its deliberation (spec 05, ADR 0006): drafted by the module
 * teacher, submitted, sent back or locked by a coordinator. Once locked it is immutable: only
 * its PV, archived in the background, is filled in once.
 *
 * @property int $id
 * @property int $exam_id
 * @property GradeSheetStatus $status
 * @property Carbon|null $submitted_at
 * @property int|null $submitted_by
 * @property Carbon|null $returned_at
 * @property string|null $return_reason
 * @property Carbon|null $locked_at
 * @property int|null $locked_by
 * @property int|null $continuous_assessment_weight The module's CC weight the sheet was locked with.
 * @property string|null $class_average
 * @property string|null $pass_rate
 * @property string|null $pv_document_path
 * @property string|null $pv_sha256
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read User|null $submitter
 * @property-read User|null $locker
 */
#[Fillable([
    'exam_id',
    'status',
    'submitted_at',
    'submitted_by',
    'returned_at',
    'return_reason',
    'locked_at',
    'locked_by',
    'continuous_assessment_weight',
    'class_average',
    'pass_rate',
    'pv_document_path',
    'pv_sha256',
])]
class ExamDeliberation extends Model
{
    /** What may still be written on a locked sheet, once: its archived PV. */
    private const array PV_ATTRIBUTES = ['pv_document_path', 'pv_sha256', 'updated_at'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::updating(function (ExamDeliberation $sheet): void {
            if (! $sheet->wasLocked()) {
                return;
            }

            $archivesPv = $sheet->getOriginal('pv_sha256') === null
                && array_diff(array_keys($sheet->getDirty()), self::PV_ATTRIBUTES) === [];

            if (! $archivesPv) {
                throw new LogicException('A locked deliberation cannot be changed.');
            }
        });

        static::deleting(function (ExamDeliberation $sheet): void {
            if ($sheet->wasLocked()) {
                throw new LogicException('A locked deliberation cannot be deleted.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GradeSheetStatus::class,
            'submitted_at' => 'datetime',
            'returned_at' => 'datetime',
            'locked_at' => 'datetime',
            'continuous_assessment_weight' => 'integer',
            'class_average' => 'decimal:2',
            'pass_rate' => 'decimal:2',
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
     * @return BelongsTo<User, $this>
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Where the archived PV is stored (private disk).
     */
    public function pvPath(): string
    {
        return "deliberation-pvs/{$this->exam_id}.pdf";
    }

    private function wasLocked(): bool
    {
        $original = $this->getOriginal('status');

        return ($original instanceof GradeSheetStatus ? $original : GradeSheetStatus::tryFrom((string) $original)) === GradeSheetStatus::Locked;
    }
}
