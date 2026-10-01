<?php

namespace App\Actions\Imports;

use App\Actions\Imports\Importers\ModuleRowImporter;
use App\Actions\Imports\Importers\RoomRowImporter;
use App\Actions\Imports\Importers\RowImporter;
use App\Actions\Imports\Importers\StudentRowImporter;
use App\Actions\Imports\Importers\TeacherRowImporter;
use App\Enums\ImportType;
use App\Exceptions\ImportFailedException;
use App\Exceptions\ImportRowException;
use App\Models\User;
use App\Support\Imports\ImportRowError;
use App\Support\Imports\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ImportReferentialsAction
{
    public function __construct(private readonly SpreadsheetReader $reader) {}

    /**
     * Validate every row of a spreadsheet and import it atomically: either all
     * rows are written (and invitations dispatched after commit) or none are.
     *
     * @return int The number of imported rows.
     *
     * @throws ImportFailedException
     */
    public function execute(ImportType $type, string $path, string $extension, User $actor): int
    {
        ['headers' => $headers, 'rows' => $rows] = $this->reader->read($path, $extension);

        $missing = array_values(array_diff($type->requiredColumns(), $headers));

        if ($missing !== []) {
            throw ImportFailedException::at(1, null, __('messages.import_missing_columns', ['columns' => implode(', ', $missing)]));
        }

        if ($rows === []) {
            throw ImportFailedException::at(2, null, __('messages.import_no_rows'));
        }

        return match ($type) {
            ImportType::Rooms => $this->importRows(app(RoomRowImporter::class), $rows, $actor),
            ImportType::Modules => $this->importRows(app(ModuleRowImporter::class), $rows, $actor),
            ImportType::Teachers => $this->importRows(app(TeacherRowImporter::class), $rows, $actor),
            ImportType::Students => $this->importRows(app(StudentRowImporter::class), $rows, $actor),
        };
    }

    /**
     * @template TPayload of array<string, mixed>
     *
     * @param  RowImporter<TPayload>  $importer
     * @param  array<int, array<string, string|null>>  $rows
     *
     * @throws ImportFailedException
     */
    private function importRows(RowImporter $importer, array $rows, User $actor): int
    {
        $payloads = $this->validateRows($importer, $rows);

        DB::transaction(function () use ($importer, $payloads, $actor): void {
            foreach ($payloads as $rowNumber => $payload) {
                try {
                    $importer->persist($payload, $actor);
                } catch (Throwable $exception) {
                    report($exception);

                    throw ImportFailedException::at($rowNumber, null, __('messages.import_row_failed'));
                }
            }
        });

        return count($payloads);
    }

    /**
     * Validate and prepare every row, collecting all errors before any write.
     *
     * @template TPayload of array<string, mixed>
     *
     * @param  RowImporter<TPayload>  $importer
     * @param  array<int, array<string, string|null>>  $rows
     * @return array<int, TPayload>
     *
     * @throws ImportFailedException
     */
    private function validateRows(RowImporter $importer, array $rows): array
    {
        $errors = [];
        $payloads = [];
        $seen = [];

        foreach ($rows as $rowNumber => $row) {
            $validator = Validator::make($row, $importer->rules());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $column => $messages) {
                    $errors[] = new ImportRowError($rowNumber, $column, $messages[0]);
                }

                continue;
            }

            foreach ($importer->uniqueKeys($row) as $column => $key) {
                if (isset($seen[$column][$key])) {
                    $errors[] = new ImportRowError($rowNumber, $column, __('messages.import_duplicate_in_file', [
                        'value' => $row[$column] ?? '',
                        'row' => $seen[$column][$key],
                    ]));

                    continue 2;
                }

                $seen[$column][$key] = $rowNumber;
            }

            try {
                $payloads[$rowNumber] = $importer->prepare($row);
            } catch (ImportRowException $exception) {
                $errors[] = new ImportRowError($rowNumber, $exception->column, $exception->getMessage());
            }
        }

        if ($errors !== []) {
            throw new ImportFailedException($errors);
        }

        return $payloads;
    }
}
