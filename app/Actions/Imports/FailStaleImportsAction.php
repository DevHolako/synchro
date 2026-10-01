<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Jobs\ProcessSpreadsheetImportJob;
use App\Models\SpreadsheetImport;
use App\Support\Imports\ImportRowError;
use Illuminate\Database\Eloquent\Builder;

class FailStaleImportsAction
{
    /**
     * A queued import not picked up within this window is given up on.
     */
    public const int PENDING_HOURS = 6;

    /**
     * Grace period past the job timeout before a processing import is presumed
     * dead (worker killed, out of memory, timeout rolled back).
     */
    public const int PROCESSING_GRACE_MINUTES = 20;

    public function __construct(private readonly RunSpreadsheetImportAction $runImport) {}

    /**
     * Mark imports that will never finish as failed, so their files are removed,
     * the history stops polling for them, and they become prunable.
     *
     * @return int The number of imports marked as failed.
     */
    public function execute(): int
    {
        $processingCutoff = now()
            ->subSeconds(ProcessSpreadsheetImportJob::TIMEOUT_SECONDS)
            ->subMinutes(self::PROCESSING_GRACE_MINUTES);

        $stale = SpreadsheetImport::query()
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('status', ImportStatus::Pending)
                    ->where('created_at', '<', now()->subHours(self::PENDING_HOURS)))
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', ImportStatus::Processing)
                    ->where('started_at', '<', $processingCutoff)))
            ->get();

        foreach ($stale as $import) {
            // Claim pending imports first, so a late worker cannot start one being failed.
            SpreadsheetImport::query()
                ->whereKey($import->id)
                ->where('status', ImportStatus::Pending)
                ->update(['status' => ImportStatus::Processing, 'started_at' => now()]);

            $this->runImport->fail($import, [
                new ImportRowError(1, null, __('messages.import_stale')),
            ]);
        }

        return $stale->count();
    }
}
