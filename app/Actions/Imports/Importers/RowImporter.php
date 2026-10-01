<?php

namespace App\Actions\Imports\Importers;

use App\Exceptions\ImportRowException;
use App\Models\User;

/**
 * Maps one referential's spreadsheet rows onto its existing domain action.
 */
interface RowImporter
{
    /**
     * Validation rules applied to each header-keyed row.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Values that must not repeat within the same file, keyed by column.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, string>
     */
    public function uniqueKeys(array $row): array;

    /**
     * Resolve references (codes, emails) and check conflicts with existing records.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     *
     * @throws ImportRowException
     */
    public function prepare(array $row): array;

    /**
     * Persist a prepared row through the referential's single action.
     *
     * @param  array<string, mixed>  $payload
     */
    public function persist(array $payload, User $actor): void;
}
