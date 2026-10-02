<?php

namespace App\Services\Scheduling;

use App\Models\User;

/**
 * A user's explicit decision to save despite soft conflicts, with the reason recorded in the audit.
 */
final readonly class SoftConflictOverride
{
    /** Bounds of a justification, in characters (mirrored by the timetable's `limits` prop). */
    public const int MIN_JUSTIFICATION = 10;

    public const int MAX_JUSTIFICATION = 1000;

    public function __construct(
        public User $user,
        public string $justification,
    ) {}
}
