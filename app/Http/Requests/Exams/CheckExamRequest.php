<?php

namespace App\Http\Requests\Exams;

use App\Http\Requests\Concerns\ReadsTypedInput;

/**
 * A would-be exam to check for conflicts without saving it (warnings while it is a draft).
 */
class CheckExamRequest extends StoreExamRequest
{
    use ReadsTypedInput;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'ignore_exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ];
    }

    public function ignoreExamId(): ?int
    {
        return $this->nullableInteger('ignore_exam_id');
    }
}
