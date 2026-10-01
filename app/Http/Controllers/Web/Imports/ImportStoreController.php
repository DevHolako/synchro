<?php

namespace App\Http\Controllers\Web\Imports;

use App\Actions\Imports\QueueSpreadsheetImportAction;
use App\Enums\ImportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ImportSpreadsheetRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ImportStoreController extends Controller
{
    public function __invoke(ImportSpreadsheetRequest $request, ImportType $type, QueueSpreadsheetImportAction $action): RedirectResponse
    {
        $import = $action->execute($type, $request->spreadsheet(), $request->user());

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => __('messages.import_queued', ['file' => $import->original_filename]),
        ]);

        return back();
    }
}
