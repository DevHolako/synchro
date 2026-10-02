<?php

namespace App\Models\Builders\Concerns;

use App\Enums\GradeSheetStatus;
use App\Models\ExamDeliberation;
use LogicException;

/**
 * Shared by the grade builders: whether written rows belong to a locked deliberation, and the
 * refusal when they do.
 */
trait RefusesLockedDeliberations
{
    /**
     * Whether any of the rows to write (one row or a list of rows) belongs to an exam whose
     * deliberation is locked.
     *
     * @param  array<int|string, mixed>  $values
     */
    protected function writesLockedExam(array $values): bool
    {
        $rows = is_array(reset($values)) ? $values : [$values];
        $examIds = array_values(array_unique(array_filter(array_map(
            fn (mixed $row): mixed => is_array($row) ? ($row['exam_id'] ?? null) : null,
            $rows,
        ))));

        return $examIds !== [] && ExamDeliberation::query()
            ->whereIn('exam_id', $examIds)
            ->where('status', GradeSheetStatus::Locked)
            ->exists();
    }

    protected function refuseIf(bool $locked): void
    {
        if ($locked) {
            throw new LogicException('A locked deliberation and its grades cannot be changed.');
        }
    }
}
