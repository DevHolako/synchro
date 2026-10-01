<?php

namespace App\Exceptions;

use App\Support\Imports\ImportRowError;
use RuntimeException;

/**
 * Raised when a spreadsheet cannot be imported; nothing has been written.
 */
class ImportFailedException extends RuntimeException
{
    /**
     * @param  list<ImportRowError>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(__('messages.import_failed', ['count' => count($errors)]));
    }

    public static function at(int $row, ?string $column, string $message): self
    {
        return new self([new ImportRowError($row, $column, $message)]);
    }
}
