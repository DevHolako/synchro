<?php

namespace App\Http\Requests\Modules;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreModuleRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Module::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Teacher->value)],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('modules', 'code')->where('program_id', $this->input('program_id')),
            ],
            'total_hours' => ['required', 'integer', 'min:1'],
            'lecture_hours' => ['required', 'integer', 'min:0'],
            'tp_hours' => ['required', 'integer', 'min:0'],
            'color_code' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $totalHours = (int) $this->input('total_hours', 0);
            $lectureHours = (int) $this->input('lecture_hours', 0);
            $tpHours = (int) $this->input('tp_hours', 0);

            if ($lectureHours + $tpHours > $totalHours) {
                $validator->errors()->add(
                    'lecture_hours',
                    __('messages.module_hours_exceed_total')
                );
            }
        });
    }

    /**
     * The validated input, typed for the action.
     *
     * @return array{program_id: int, teacher_id: int|null, name: string, code: string, total_hours: int, lecture_hours: int, tp_hours: int, color_code: string, description: string|null, is_active?: bool}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'program_id' => $this->integer('program_id'),
            'teacher_id' => $this->nullableInteger('teacher_id'),
            'name' => $this->string('name')->value(),
            'code' => $this->string('code')->value(),
            'total_hours' => $this->integer('total_hours'),
            'lecture_hours' => $this->integer('lecture_hours'),
            'tp_hours' => $this->integer('tp_hours'),
            'color_code' => $this->string('color_code')->value(),
            'description' => $this->nullableString('description'),
            ...$this->activeFlag(),
        ];
    }
}
