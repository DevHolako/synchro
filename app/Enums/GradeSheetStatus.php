<?php

namespace App\Enums;

/**
 * Where an exam's grade sheet stands (ADR 0006): the module teacher's draft, submitted for
 * deliberation, then locked by the coordinator.
 */
enum GradeSheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Locked = 'locked';

    /**
     * Whether grades may still change on a sheet in this status, by the teacher or by a weight change.
     */
    public function isOpen(): bool
    {
        return $this !== self::Locked;
    }
}
