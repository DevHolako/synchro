<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Exceptions\ImportFailedException;
use App\Models\SpreadsheetImport;
use App\Support\Imports\ImportRowError;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Scoped so that a worker's timeout handler, which calls fail() while
 * execute() is still running, reaches the same instance within the job.
 */
#[Scoped]
class RunSpreadsheetImportAction
{
    public const int MAX_STORED_ERRORS = 200;

    /**
     * Transaction depth of the connection when the running import started, or
     * null when no import is running in this instance.
     */
    private ?int $transactionLevel = null;

    public function __construct(private readonly ImportReferentialsAction $importReferentials) {}

    /**
     * Process a pending import and record its outcome. Re-running a finished or
     * in-flight import is a no-op, so duplicate deliveries cannot import twice.
     */
    public function execute(SpreadsheetImport $import): SpreadsheetImport
    {
        $claimed = SpreadsheetImport::query()
            ->whereKey($import->id)
            ->where('status', ImportStatus::Pending)
            ->update(['status' => ImportStatus::Processing, 'started_at' => now()]);

        if ($claimed === 0) {
            return $import->refresh();
        }

        $import->refresh();
        $this->transactionLevel = DB::transactionLevel();

        try {
            $count = $this->importReferentials->execute(
                $import->type,
                Storage::disk($import->disk)->path((string) $import->path),
                $import->extension,
                $import->user,
            );

            $import->forceFill([
                'status' => ImportStatus::Succeeded,
                'imported_count' => $count,
            ]);
        } catch (ImportFailedException $exception) {
            $this->markFailed($import, $exception->errors);
        } finally {
            $this->transactionLevel = null;
            $this->finish($import);
        }

        return $import;
    }

    /**
     * Record an import that will never finish on its own (worker timeout or
     * crash, unreachable queue, stale record) as failed and drop its file.
     *
     * A worker timeout calls this from its signal handler while execute() may
     * still be inside the import's transaction, then kills the process. That
     * transaction is rolled back first, or it would take this failure with it.
     *
     * @param  list<ImportRowError>  $errors
     */
    public function fail(SpreadsheetImport $import, array $errors): void
    {
        if ($this->transactionLevel !== null) {
            DB::rollBack($this->transactionLevel);
            $this->transactionLevel = null;
        }

        $import->refresh();

        if ($import->status->isFinished()) {
            return;
        }

        $this->markFailed($import, $errors);
        $this->finish($import);
    }

    /**
     * @param  list<ImportRowError>  $errors
     */
    private function markFailed(SpreadsheetImport $import, array $errors): void
    {
        $import->forceFill([
            'status' => ImportStatus::Failed,
            'imported_count' => 0,
            'error_count' => count($errors),
            'errors' => array_map(
                fn (ImportRowError $error): array => $error->toArray(),
                array_slice($errors, 0, self::MAX_STORED_ERRORS),
            ),
        ]);
    }

    /**
     * Save the outcome and remove the uploaded spreadsheet, which is no longer needed.
     */
    private function finish(SpreadsheetImport $import): void
    {
        $path = $import->path;

        $import->forceFill(['finished_at' => now(), 'path' => null])->save();

        if ($path !== null) {
            Storage::disk($import->disk)->delete($path);
        }
    }
}
