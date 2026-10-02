<?php

namespace App\Http\Requests\Grades;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class ReturnGradeSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Exam $exam */
        $exam = $this->route('exam');

        return $this->user()?->can('lockGrades', $exam) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function reason(): string
    {
        return $this->string('reason')->trim()->value();
    }
}
