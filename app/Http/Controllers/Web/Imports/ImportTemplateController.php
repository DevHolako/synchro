<?php

namespace App\Http\Controllers\Web\Imports;

use App\Enums\ImportType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportTemplateController extends Controller
{
    public function __invoke(Request $request, ImportType $type): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->hasPermission(Permission::ImportReferentials) && $user->can(...$type->ability()), 403);

        return response()->streamDownload(function () use ($type): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM so spreadsheet apps keep accents.
            fputcsv($output, $type->columns(), escape: '');
            fputcsv($output, $type->exampleRow(), escape: '');
            fclose($output);
        }, "synchro-{$type->value}-template.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
