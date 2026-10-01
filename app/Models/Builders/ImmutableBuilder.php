<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * An Eloquent builder for append-only records: bulk updates and deletes are refused too,
 * not just per-model saves.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class ImmutableBuilder extends Builder
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): int
    {
        throw new LogicException('Append-only records cannot be updated.');
    }

    public function delete(): mixed
    {
        throw new LogicException('Append-only records cannot be deleted.');
    }

    public function forceDelete(): mixed
    {
        throw new LogicException('Append-only records cannot be deleted.');
    }
}
