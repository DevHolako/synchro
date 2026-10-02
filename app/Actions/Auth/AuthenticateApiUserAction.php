<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateApiUserAction
{
    /**
     * Authenticate an API user with email and password and issue a personal access token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function execute(string $email, string $password, ?string $deviceName = 'mobile'): array
    {
        /** @var User|null $user */
        $user = User::query()
            ->with(['studentProfile.studentGroup', 'teacherProfile'])
            ->where('email', $email)
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => [__('messages.account_inactive')],
            ]);
        }

        $tokenName = $deviceName ?: 'mobile';
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
