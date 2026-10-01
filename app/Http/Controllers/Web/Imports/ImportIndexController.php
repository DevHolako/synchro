<?php

namespace App\Http\Controllers\Web\Imports;

use App\Enums\ImportType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ImportSpreadsheetRequest;
use App\Support\Imports\SpreadsheetReader;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->hasPermission(Permission::ImportReferentials), 403);

        $types = collect(ImportType::cases())
            ->filter(fn (ImportType $type): bool => $user->can(...$type->ability()))
            ->map(fn (ImportType $type): array => [
                'type' => $type->value,
                'columns' => $type->columns(),
                'required' => $type->requiredColumns(),
            ])
            ->values();

        return Inertia::render('imports/index', [
            'types' => $types,
            'limits' => [
                'max_rows' => SpreadsheetReader::MAX_DATA_ROWS,
                'max_kilobytes' => ImportSpreadsheetRequest::MAX_KILOBYTES,
            ],
        ]);
    }
}
