<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * An Eloquent builder for append-only records: every update path (model saves, bulk
 * updates, increments, upserts) and every delete is refused. Raw `DB::table()` access is
 * deliberately out of reach of this guard.
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

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int|string, mixed>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        throw new LogicException('Append-only records cannot be updated.');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function increment($column, $amount = 1, array $extra = []): int
    {
        throw new LogicException('Append-only records cannot be updated.');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function decrement($column, $amount = 1, array $extra = []): int
    {
        throw new LogicException('Append-only records cannot be updated.');
    }

    /**
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    public function incrementEach(array $columns, array $extra = []): int
    {
        throw new LogicException('Append-only records cannot be updated.');
    }

    /**
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    public function decrementEach(array $columns, array $extra = []): int
    {
        throw new LogicException('Append-only records cannot be updated.');
    }
}
