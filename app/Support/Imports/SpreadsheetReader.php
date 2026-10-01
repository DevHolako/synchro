<?php

namespace App\Support\Imports;

use App\Exceptions\ImportFailedException;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Reads the first sheet of a CSV or XLSX file into header-keyed rows, keeping
 * the original spreadsheet row numbers so errors can point at exact lines.
 */
class SpreadsheetReader
{
    public const int MAX_DATA_ROWS = 2000;

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string|null>>}
     *
     * @throws ImportFailedException
     */
    public function read(string $path, string $extension): array
    {
        $reader = $this->makeReader($path, strtolower($extension));

        $headers = [];
        $rows = [];

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                $rowNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $cells = array_map($this->normalizeCell(...), $row->toArray());

                    if ($rowNumber === 1) {
                        $headers = array_map($this->normalizeHeader(...), $cells);

                        continue;
                    }

                    if (array_filter($cells, fn (?string $cell): bool => $cell !== null) === []) {
                        continue;
                    }

                    if (count($rows) >= self::MAX_DATA_ROWS) {
                        throw ImportFailedException::at($rowNumber, null, __('messages.import_too_many_rows', ['max' => self::MAX_DATA_ROWS]));
                    }

                    $rows[$rowNumber] = $this->combine($headers, $cells);
                }

                break; // Only the first sheet is imported.
            }
        } catch (OpenSpoutException) {
            throw ImportFailedException::at(1, null, __('messages.import_unreadable'));
        } finally {
            $reader->close();
        }

        return ['headers' => array_values(array_filter($headers)), 'rows' => $rows];
    }

    private function makeReader(string $path, string $extension): CsvReader|XlsxReader
    {
        if ($extension === 'xlsx') {
            return new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));
        }

        $sample = (string) file_get_contents($path, length: 65536);
        $firstLine = strtok($sample, "\r\n") ?: '';

        $delimiter = collect([',', ';', "\t"])
            ->sortByDesc(fn (string $candidate): int => substr_count($firstLine, $candidate))
            ->first();

        return new CsvReader(new CsvOptions(
            SHOULD_PRESERVE_EMPTY_ROWS: true,
            FIELD_DELIMITER: $delimiter,
            // Excel on Windows commonly saves CSV as Windows-1252 rather than UTF-8.
            ENCODING: mb_check_encoding($sample, 'UTF-8') ? 'UTF-8' : 'Windows-1252',
        ));
    }

    private function normalizeCell(mixed $value): ?string
    {
        $value = match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_float($value) && floor($value) === $value => (string) (int) $value,
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeHeader(?string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? '';

        return Str::of($header)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $cells
     * @return array<string, string|null>
     */
    private function combine(array $headers, array $cells): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $row[$header] = $cells[$index] ?? null;
            }
        }

        return $row;
    }
}
