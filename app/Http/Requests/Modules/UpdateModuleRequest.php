<?php

namespace App\Http\Requests\Modules;

use App\Enums\UserRole;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Module|null $module */
        $module = $this->route('module');

        return $module !== null && ($this->user()?->can('update', $module) ?? false);
    }

    /**
     * Validate scoped uniqueness and cross-field invariants against the stored values
     * when a partial update omits them (e.g. moving a record to another parent).
     */
    protected function prepareForValidation(): void
    {
        /** @var Module $module */
        $module = $this->route('module');

        $this->mergeIfMissing([
            'code' => $module->code,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Module $module */
        $module = $this->route('module');
        $programId = $this->input('program_id', $module->program_id);

        return [
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Teacher->value)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('modules', 'code')
                    ->where('program_id', $programId)
                    ->ignore($module->id),
            ],
            'total_hours' => ['sometimes', 'required', 'integer', 'min:1'],
            'lecture_hours' => ['sometimes', 'required', 'integer', 'min:0'],
            'tp_hours' => ['sometimes', 'required', 'integer', 'min:0'],
            'color_code' => ['sometimes', 'required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Module $module */
            $module = $this->route('module');

            $totalHours = array_key_exists('total_hours', $this->all())
                ? (int) $this->input('total_hours')
                : $module->total_hours;

            $lectureHours = array_key_exists('lecture_hours', $this->all())
                ? (int) $this->input('lecture_hours')
                : $module->lecture_hours;

            $tpHours = array_key_exists('tp_hours', $this->all())
                ? (int) $this->input('tp_hours')
                : $module->tp_hours;

            if ($lectureHours + $tpHours > $totalHours) {
                $validator->errors()->add(
                    'lecture_hours',
                    'The sum of lecture hours and practical work (TP) hours cannot exceed total syllabus hours.'
                );
            }
        });
    }
}
