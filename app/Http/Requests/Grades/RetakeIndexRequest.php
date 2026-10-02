<?php

namespace App\Http\Requests\Grades;

use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class RetakeIndexRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Exam::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'integer'],
        ];
    }

    public function periodId(): ?int
    {
        return $this->nullableInteger('period');
    }
}
