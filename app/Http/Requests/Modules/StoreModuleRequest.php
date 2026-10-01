<?php

namespace App\Http\Requests\Modules;

use App\Enums\UserRole;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreModuleRequest extends FormRequest
{
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
                    'The sum of lecture hours and practical work (TP) hours cannot exceed total syllabus hours.'
                );
            }
        });
    }
}
