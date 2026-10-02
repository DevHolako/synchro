<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Permission;
use App\Services\Scheduling\SoftConflictOverride;

/**
 * A write that may knowingly override soft conflicts (ADR 0002): `force_override` with a
 * `justification`, which needs its own permission.
 */
trait ValidatesConflictOverride
{
    /**
     * Asking to override soft conflicts needs its own permission.
     */
    protected function mayOverride(): bool
    {
        return ! $this->boolean('force_override')
            || ($this->user()?->hasPermission(Permission::OverrideSoftConflicts) ?? false);
    }

    /**
     * A justification only counts alongside `force_override`.
     */
    protected function prepareOverrideInput(): void
    {
        if (! $this->boolean('force_override')) {
            $this->merge(['justification' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function overrideRules(): array
    {
        return [
            'force_override' => ['sometimes', 'boolean'],
            'justification' => [
                'nullable',
                'required_if_accepted:force_override',
                'string',
                'min:'.SoftConflictOverride::MIN_JUSTIFICATION,
                'max:'.SoftConflictOverride::MAX_JUSTIFICATION,
            ],
        ];
    }

    /**
     * The user's override of soft conflicts, when they asked for one.
     */
    public function softConflictOverride(): ?SoftConflictOverride
    {
        $user = $this->user();

        if (! $this->boolean('force_override') || $user === null) {
            return null;
        }

        return new SoftConflictOverride($user, $this->string('justification')->trim()->value());
    }
}
