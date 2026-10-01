<?php

namespace App\Services\Scheduling;

use App\Models\User;

/**
 * A user's explicit decision to save despite soft conflicts, with the reason recorded in the audit.
 */
final readonly class SoftConflictOverride
{
    public function __construct(
        public User $user,
        public string $justification,
    ) {}
}
