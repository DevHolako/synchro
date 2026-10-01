<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised by a row importer when a row references missing or conflicting data.
 */
class ImportRowException extends RuntimeException
{
    public function __construct(public readonly ?string $column, string $message)
    {
        parent::__construct($message);
    }
}
