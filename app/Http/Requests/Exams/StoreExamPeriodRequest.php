<?php

namespace App\Http\Requests\Exams;

use App\Enums\ExamSessionType;
use App\Models\ExamPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExamPeriod::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'session_type' => ['required', Rule::enum(ExamSessionType::class)],
            'academic_year' => ['required', 'string', 'max:20'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * The validated period, typed for the actions.
     *
     * @return array{name: string, session_type: string, academic_year: string, start_date: string, end_date: string}
     */
    public function payload(): array
    {
        $this->validated();

        return [
            'name' => $this->string('name')->trim()->value(),
            'session_type' => $this->string('session_type')->value(),
            'academic_year' => $this->string('academic_year')->trim()->value(),
            'start_date' => $this->string('start_date')->value(),
            'end_date' => $this->string('end_date')->value(),
        ];
    }
}
