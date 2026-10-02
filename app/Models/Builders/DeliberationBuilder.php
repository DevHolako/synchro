<?php

namespace App\Models\Builders;

use App\Enums\GradeSheetStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * An Eloquent builder for deliberations: once locked, one is immutable (ADR 0006). Every update
 * path that reaches a locked deliberation is refused, except filling in its archived PV once;
 * deletes and upserts of locked ones are refused too. Raw `DB::table()` access is deliberately
 * out of reach of this guard, as for `ImmutableBuilder`.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class DeliberationBuilder extends Builder
{
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

            if (! $archivesPv) {
                throw new LogicException('A locked deliberation cannot be changed.');
            }
        }

        return parent::update($values);
    }

    public function delete(): mixed
    {
        if ($this->touchesLocked()) {
            throw new LogicException('A locked deliberation cannot be deleted.');
        }

        return parent::delete();
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int|string, mixed>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        $rows = is_array(reset($values)) ? $values : [$values];
        $examIds = array_values(array_filter(array_map(fn (mixed $row): mixed => is_array($row) ? ($row['exam_id'] ?? null) : null, $rows)));

        if ($examIds !== [] && $this->newModelInstance()->newQuery()->whereIn('exam_id', $examIds)->where('status', GradeSheetStatus::Locked)->exists()) {
            throw new LogicException('A locked deliberation cannot be changed.');
        }

        return parent::upsert($values, $uniqueBy, $update);
    }

    private function touchesLocked(): bool
    {
        return (clone $this)->where('status', GradeSheetStatus::Locked)->exists();
    }
}
