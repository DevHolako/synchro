<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Database\Factories\SpreadsheetImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A spreadsheet upload processed asynchronously on the `imports` queue.
 *
 * @property int $id
 * @property int $user_id
 * @property ImportType $type
 * @property ImportStatus $status
 * @property string $original_filename
 * @property string $disk
 * @property string|null $path
 * @property string $extension
 * @property int $imported_count
 * @property int $error_count
 * @property list<array{row: int, column: string|null, message: string}>|null $errors
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id', 'type', 'status', 'original_filename', 'disk', 'path', 'extension',
    'imported_count', 'error_count', 'errors', 'started_at', 'finished_at',
])]
#[Hidden(['disk', 'path'])]
class SpreadsheetImport extends Model
{
    /** @use HasFactory<SpreadsheetImportFactory> */
    use HasFactory, MassPrunable;

    public const int RETENTION_DAYS = 90;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
            'imported_count' => 'integer',
            'error_count' => 'integer',
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Remove the uploaded spreadsheet once it is no longer needed.
     */
    public function deleteStoredFile(): void
    {
        if ($this->path !== null) {
            Storage::disk($this->disk)->delete($this->path);
            $this->forceFill(['path' => null])->save();
        }
    }

    /**
     * Finished import records older than the retention window.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereIn('status', [ImportStatus::Succeeded, ImportStatus::Failed])
            ->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
