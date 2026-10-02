<?php

namespace App\Models\Builders;

use App\Enums\GradeSheetStatus;
use App\Models\Builders\Concerns\RefusesLockedDeliberations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent builder for deliberations: once locked, one is immutable (ADR 0006). Every write
 * path that reaches a locked deliberation (model saves and deletes, bulk updates and deletes,
 * increments, upserts) is refused, except filling in its archived PV once. Raw `DB::table()`
 * access is deliberately out of reach of this guard, as for `ImmutableBuilder`.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class DeliberationBuilder extends Builder
{
    use RefusesLockedDeliberations;

    /** What may still be written on a locked deliberation, once: its archived PV. */
    private const array PV_ATTRIBUTES = ['pv_document_path', 'pv_sha256', 'updated_at'];

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): int
    {
        if ($this->touchesLocked()) {
            $archivesPv = array_diff(array_keys($values), self::PV_ATTRIBUTES) === []
                && ! (clone $this)->where('status', GradeSheetStatus::Locked)->whereNotNull('pv_sha256')->exists();

            $this->refuseIf(! $archivesPv);
        }

        return parent::update($values);
    }

    public function delete(): mixed
    {
        $this->refuseIf($this->touchesLocked());

        return parent::delete();
    }

    public function forceDelete(): mixed
    {
        $this->refuseIf($this->touchesLocked());

        return parent::forceDelete();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function increment($column, $amount = 1, array $extra = []): int
    {
        $this->refuseIf($this->touchesLocked());

        return parent::increment($column, $amount, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function decrement($column, $amount = 1, array $extra = []): int
    {
        $this->refuseIf($this->touchesLocked());

        return parent::decrement($column, $amount, $extra);
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

    private function touchesLocked(): bool
    {
        return (clone $this)->where('status', GradeSheetStatus::Locked)->exists();
    }
}
