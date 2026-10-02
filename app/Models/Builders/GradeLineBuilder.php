<?php

namespace App\Models\Builders;

use App\Enums\GradeSheetStatus;
use App\Models\Builders\Concerns\RefusesLockedDeliberations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent builder for grade lines: once their deliberation is locked they are immutable
 * (spec 05, ADR 0006), so every write path that reaches such a line (model creates, saves and
 * deletes, bulk updates and deletes, increments, upserts and inserts) is refused. Raw
 * `DB::table()` access is deliberately out of reach of this guard, as for `ImmutableBuilder`.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class GradeLineBuilder extends Builder
{
    use RefusesLockedDeliberations;

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): int
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::update($values);
    }

    public function delete(): mixed
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::delete();
    }

    public function forceDelete(): mixed
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::forceDelete();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function increment($column, $amount = 1, array $extra = []): int
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::increment($column, $amount, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function decrement($column, $amount = 1, array $extra = []): int
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::decrement($column, $amount, $extra);
    }

    /**
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    public function incrementEach(array $columns, array $extra = []): int
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::incrementEach($columns, $extra);
    }

    /**
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    public function decrementEach(array $columns, array $extra = []): int
    {
        $this->refuseIf($this->touchesLockedLine());

        return parent::decrementEach($columns, $extra);
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int|string, mixed>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        $this->refuseIf($this->writesLockedExam($values));

        return parent::upsert($values, $uniqueBy, $update);
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    public function insert(array $values): bool
    {
        $this->refuseIf($this->writesLockedExam($values));

        return $this->toBase()->insert($values);
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    public function insertOrIgnore(array $values): int
    {
        $this->refuseIf($this->writesLockedExam($values));

        return $this->toBase()->insertOrIgnore($values);
    }

    /**
     * The path of a model create.
     *
     * @param  array<string, mixed>  $values
     * @param  string|null  $sequence
     */
    public function insertGetId(array $values, $sequence = null): int
    {
        $this->refuseIf($this->writesLockedExam($values));

        return (int) $this->toBase()->insertGetId($values, $sequence);
    }

    private function touchesLockedLine(): bool
    {
        return (clone $this)
            ->whereHas('exam.deliberation', fn (Builder $sheets) => $sheets->where('status', GradeSheetStatus::Locked))
            ->exists();
    }
}
