<?php

namespace App\Jobs;

use App\Actions\Imports\RunSpreadsheetImportAction;
use App\Models\SpreadsheetImport;
use App\Support\Imports\ImportRowError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessSpreadsheetImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Imports are atomic but not blindly retryable: a crash after commit must
     * not replay the file, so a single attempt is made.
     */
    public int $tries = 1;

    /**
     * Must stay below the `supervisor-imports` timeout in config/horizon.php.
     */
    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public SpreadsheetImport $import)
    {
        $this->onQueue('imports');
    }

    public function handle(RunSpreadsheetImportAction $action): void
    {
        $action->execute($this->import);
    }

    /**
     * Record unexpected failures (timeouts, crashes) so the uploader is not left waiting.
     */
    public function failed(?Throwable $exception): void
    {
        $import = $this->import->refresh();

        if ($import->status->isFinished()) {
            return;
        }

        app(RunSpreadsheetImportAction::class)->markFailed($import, [
            new ImportRowError(1, null, __('messages.import_crashed')),
        ]);

        $import->forceFill(['finished_at' => now()])->save();
        $import->deleteStoredFile();
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['import:'.$this->import->type->value, 'user:'.$this->import->user_id];
    }
}
