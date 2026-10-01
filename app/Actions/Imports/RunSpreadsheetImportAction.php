<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Exceptions\ImportFailedException;
use App\Models\SpreadsheetImport;
use App\Support\Imports\ImportRowError;
use Illuminate\Support\Facades\Storage;

class RunSpreadsheetImportAction
{
    public const int MAX_STORED_ERRORS = 200;

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
            $import->forceFill(['finished_at' => now()])->save();
            $import->deleteStoredFile();
        }

        return $import;
    }

    /**
     * @param  list<ImportRowError>  $errors
     */
    public function markFailed(SpreadsheetImport $import, array $errors): void
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
}
