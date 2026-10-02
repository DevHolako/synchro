<?php

namespace App\Http\Requests\Grades;

use App\Actions\Grades\ListDeliberationsAction;
use App\Enums\GradeSheetStatus;
use App\Enums\Permission;
use App\Http\Requests\Concerns\ReadsTypedInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliberationIndexRequest extends FormRequest
{
    use ReadsTypedInput;

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::LockGrades->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([ListDeliberationsAction::NOT_STARTED, ...array_column(GradeSheetStatus::cases(), 'value')])],
        ];
    }

    public function periodId(): ?int
    {
        return $this->nullableInteger('period');
    }

    public function status(): ?string
    {
        return $this->nullableString('status');
    }
}
