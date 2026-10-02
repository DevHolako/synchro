<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Permission;
use App\Services\Scheduling\SoftConflictOverride;

/**
 * A write that may knowingly override soft conflicts (ADR 0002): a flag (`force_override`
 * unless the request names it otherwise) with a `justification`, which needs its own permission.
 */
trait ValidatesConflictOverride
{
    /**
     * The input flag that asks for the override; a request may name it after what it overrides.
     */
    protected function overrideFlag(): string
    {
        return 'force_override';
    }

    /**
     * Asking to override soft conflicts needs its own permission.
     */
    protected function mayOverride(): bool
    {
        return ! $this->boolean($this->overrideFlag())
            || ($this->user()?->hasPermission(Permission::OverrideSoftConflicts) ?? false);
    }

    /**
     * A justification only counts alongside `force_override`.
     */
    protected function prepareOverrideInput(): void
    {
        if (! $this->boolean($this->overrideFlag())) {
            $this->merge(['justification' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function overrideRules(): array
    {
        return [
            $this->overrideFlag() => ['sometimes', 'boolean'],
            'justification' => [
                'nullable',
                'required_if_accepted:'.$this->overrideFlag(),
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

        if (! $this->boolean($this->overrideFlag()) || $user === null) {
            return null;
        }

        return new SoftConflictOverride($user, $this->string('justification')->trim()->value());
    }
}
