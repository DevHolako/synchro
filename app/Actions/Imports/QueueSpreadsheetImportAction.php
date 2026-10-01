<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Jobs\ProcessSpreadsheetImportJob;
use App\Models\SpreadsheetImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class QueueSpreadsheetImportAction
{
    public const string DISK = 'local';

    /**
     * Store the uploaded spreadsheet and hand it to the `imports` queue.
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

        ProcessSpreadsheetImportJob::dispatch($import);

        return $import;
    }
}
