<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }

    /**
     * Accounts that taught a session or overrode a scheduling conflict anchor the timetable
     * and its audit trail, so they cannot be deleted (deactivation is the way out).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if ($user === null || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $anchored = CourseSession::query()->where('teacher_id', $user->id)->exists()
                    || ConflictOverride::query()->where('user_id', $user->id)->exists();

                if ($anchored) {
                    $validator->errors()->add('account', __('messages.account_has_scheduling_history'));
                }
            },
        ];
    }
}
