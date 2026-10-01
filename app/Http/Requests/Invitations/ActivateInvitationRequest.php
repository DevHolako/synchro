<?php

namespace App\Http\Requests\Invitations;

use App\Concerns\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ActivateInvitationRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * The invitation itself (signed URL + single-use token) is the credential.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }
}
