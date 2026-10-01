<?php

namespace App\Http\Requests\Departments;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The validated input, typed for the action.
     *
     * @return array{name: string, code: string, description: string|null, is_active?: bool}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'name' => $this->string('name')->value(),
            'code' => $this->string('code')->value(),
            'description' => $this->nullableString('description'),
            ...$this->activeFlag(),
        ];
    }
}
