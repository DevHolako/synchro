<?php

namespace App\Support\Imports;

/**
 * A single import problem located at a spreadsheet row (1-based, header = row 1).
 */
final readonly class ImportRowError
{
    public function __construct(
        public int $row,
        public ?string $column,
        public string $message,
    ) {}

    /**
     * @return array{row: int, column: string|null, message: string}
     */
    public function toArray(): array
    {
        return ['row' => $this->row, 'column' => $this->column, 'message' => $this->message];
    }
}
