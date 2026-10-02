<?php

namespace App\Models\Builders;

use App\Enums\GradeSheetStatus;
use App\Models\ExamDeliberation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * An Eloquent builder for grade lines: once their deliberation is locked they are immutable
 * (spec 05, ADR 0006), so every write path that reaches such a line (model saves and deletes,
 * bulk updates and deletes, upserts and inserts) is refused. Raw `DB::table()` access is
 * deliberately out of reach of this guard, as for `ImmutableBuilder`.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class GradeLineBuilder extends Builder
{
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

    /**
     * @param  array<int|string, mixed>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int|string, mixed>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        $this->refuseIf($this->locksAnyExamOf($values));

        return parent::upsert($values, $uniqueBy, $update);
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    public function insert(array $values): bool
    {
        $this->refuseIf($this->locksAnyExamOf($values));

        return $this->toBase()->insert($values);
    }

    private function touchesLockedLine(): bool
    {
        return (clone $this)
            ->whereHas('exam.deliberation', fn (Builder $sheets) => $sheets->where('status', GradeSheetStatus::Locked))
            ->exists();
    }

    /**
     * @param  array<int|string, mixed>  $values  One row or a list of rows.
     */
    private function locksAnyExamOf(array $values): bool
    {
        $rows = is_array(reset($values)) ? $values : [$values];
        $examIds = array_values(array_unique(array_filter(array_map(fn (mixed $row): mixed => is_array($row) ? ($row['exam_id'] ?? null) : null, $rows))));

        return $examIds !== [] && ExamDeliberation::query()
            ->whereIn('exam_id', $examIds)
            ->where('status', GradeSheetStatus::Locked)
            ->exists();
    }

    private function refuseIf(bool $locked): void
    {
        if ($locked) {
            throw new LogicException('Grades of a locked deliberation cannot be changed.');
        }
    }
}
