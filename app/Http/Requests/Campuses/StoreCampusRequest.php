<?php

namespace App\Http\Requests\Campuses;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Campus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampusRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Campus::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('campuses', 'code')],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * The validated input, typed for the action.
     *
     * @return array{name: string, code: string, address: string|null, city: string|null, is_active?: bool}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'name' => $this->string('name')->value(),
            'code' => $this->string('code')->value(),
            'address' => $this->nullableString('address'),
            'city' => $this->nullableString('city'),
            ...$this->activeFlag(),
        ];
    }
}
