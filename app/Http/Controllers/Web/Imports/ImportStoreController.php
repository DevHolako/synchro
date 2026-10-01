<?php

namespace App\Http\Controllers\Web\Imports;

use App\Actions\Imports\ImportReferentialsAction;
use App\Enums\ImportType;
use App\Exceptions\ImportFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ImportSpreadsheetRequest;
use App\Support\Imports\ImportRowError;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ImportStoreController extends Controller
{
    private const int MAX_REPORTED_ERRORS = 200;

    public function __invoke(ImportSpreadsheetRequest $request, ImportType $type, ImportReferentialsAction $action): RedirectResponse
    {
        $file = $request->spreadsheet();

        try {
            $count = $action->execute($type, $file->getRealPath(), $file->getClientOriginalExtension(), $request->user());
        } catch (ImportFailedException $exception) {
            Inertia::flash([
                'toast' => ['type' => 'error', 'message' => $exception->getMessage()],
                'import_report' => [
                    'status' => 'failed',
                    'type' => $type->value,
                    'file' => $file->getClientOriginalName(),
                    'total_errors' => count($exception->errors),
                    'errors' => array_map(
                        fn (ImportRowError $error): array => $error->toArray(),
                        array_slice($exception->errors, 0, self::MAX_REPORTED_ERRORS),
                    ),
                ],
            ]);

            return back();
        }

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('messages.import_succeeded', ['count' => $count])],
            'import_report' => [
                'status' => 'succeeded',
                'type' => $type->value,
                'file' => $file->getClientOriginalName(),
                'imported' => $count,
            ],
        ]);

        return back();
    }
}
