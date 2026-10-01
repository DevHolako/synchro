<?php

namespace App\Http\Requests\Concerns;

/**
 * Typed accessors for building an action payload from already-validated input.
 *
 * `validated()` is typed `array<string, mixed>`, which cannot satisfy the actions'
 * array-shape parameters; requests use these helpers to hand actions typed payloads.
 */
trait ReadsTypedInput
{
    protected function nullableString(string $key): ?string
    {
        return $this->filled($key) ? $this->string($key)->value() : null;
    }

    protected function nullableInteger(string $key): ?int
    {
        return $this->filled($key) ? $this->integer($key) : null;
    }

    /**
     * The flag only when the client sent one, so the action's default applies otherwise.
     *
     * @return array{is_active?: bool}
     */
    protected function activeFlag(): array
    {
        return $this->input('is_active') === null ? [] : ['is_active' => $this->boolean('is_active')];
    }
}
