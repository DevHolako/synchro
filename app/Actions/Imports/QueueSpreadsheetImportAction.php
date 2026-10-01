<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Jobs\ProcessSpreadsheetImportJob;
use App\Models\SpreadsheetImport;
use App\Models\User;
use App\Support\Imports\ImportRowError;
use Illuminate\Http\UploadedFile;
use Throwable;

class QueueSpreadsheetImportAction
{
    public const string DISK = 'local';

    public function __construct(private readonly RunSpreadsheetImportAction $runImport) {}

    /**
     * Store the uploaded spreadsheet and hand it to the `imports` queue. If the
     * queue is unreachable, the import is recorded as failed rather than left pending.
     */
    public function execute(ImportType $type, UploadedFile $file, User $actor): SpreadsheetImport
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $import = SpreadsheetImport::create([
            'user_id' => $actor->id,
            'type' => $type,
            'status' => ImportStatus::Pending,
            'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'disk' => self::DISK,
            'path' => $file->store('imports', self::DISK),
            'extension' => $extension,
        ]);

        try {
            ProcessSpreadsheetImportJob::dispatch($import);
        } catch (Throwable $exception) {
            report($exception);

            $this->runImport->fail($import, [
                new ImportRowError(1, null, __('messages.import_queue_unavailable')),
            ]);
        }

        return $import;
    }
}
