<?php

namespace App\Services\Scheduling;

/**
 * A scheduling policy that warns rather than blocks; an authorized user may override it (ADR 0002).
 */
interface SoftConflictRule
{
    /**
     * @return list<Conflict>
     */
    public function softConflicts(SessionSlot $slot): array;
}
