<?php

namespace App\Actions\Auth;

use App\Models\User;

class RevokeApiTokenAction
{
    /**
     * Revoke the user's current personal access token.
     */
    public function execute(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
