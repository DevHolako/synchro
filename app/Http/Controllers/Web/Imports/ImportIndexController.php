<?php

namespace App\Http\Controllers\Web\Imports;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ImportSpreadsheetRequest;
use App\Models\SpreadsheetImport;
use App\Models\User;
use App\Support\Imports\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ImportIndexController extends Controller
{
    private const int RECENT_IMPORTS = 15;

    /**
     * The history is polled while an import runs, so it omits the error reports,
     * which are loaded one at a time through the optional `report` prop.
     */
    private const array HISTORY_COLUMNS = [
        'id', 'user_id', 'type', 'status', 'original_filename',
        'imported_count', 'error_count', 'created_at', 'finished_at',
    ];

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->hasPermission(Permission::ImportReferentials), 403);

        return Inertia::render('imports/index', [
            'types' => fn (): Collection => collect($this->allowedTypes($user))->map(fn (ImportType $type): array => [
                'type' => $type->value,
                'columns' => $type->columns(),
                'required' => $type->requiredColumns(),
            ])->values(),
            'imports' => fn (): Collection => SpreadsheetImport::query()
                ->select(self::HISTORY_COLUMNS)
                ->with('user:id,name')
                ->whereIn('type', $this->allowedTypes($user))
                ->latest('id')
                ->limit(self::RECENT_IMPORTS)
                ->get(),
            'report' => Inertia::optional(fn (): ?SpreadsheetImport => SpreadsheetImport::query()
                ->select(['id', 'original_filename', 'error_count', 'errors'])
                ->whereKey($request->integer('report'))
                ->where('status', ImportStatus::Failed)
                ->whereIn('type', $this->allowedTypes($user))
                ->first()),
            'limits' => fn (): array => [
                'max_rows' => SpreadsheetReader::MAX_DATA_ROWS,
                'max_kilobytes' => ImportSpreadsheetRequest::MAX_KILOBYTES,
            ],
        ]);
    }

    /**
     * @return list<ImportType>
     */
    private function allowedTypes(User $user): array
    {
        return array_values(array_filter(
            ImportType::cases(),
            fn (ImportType $type): bool => $user->can(...$type->ability()),
        ));
    }
}
