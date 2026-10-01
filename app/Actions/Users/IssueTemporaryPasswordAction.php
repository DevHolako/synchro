<?php

namespace App\Actions\Users;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueTemporaryPasswordAction
{
    /**
     * Replace the user's password with a generated temporary one, activating the
     * account and revoking any outstanding invitations. Returns the plain password
     * so the administrator can relay it once.
     */
    public function execute(User $user): string
    {
        $temporaryPassword = Str::password(16, symbols: false);

        DB::transaction(function () use ($user, $temporaryPassword): void {
            $user->forceFill([
                'password' => $temporaryPassword,
                'status' => AccountStatus::Active,
                'activated_at' => $user->activated_at ?? now(),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'remember_token' => Str::random(60),
            ])->save();

            $user->invitationTokens()->pending()->update(['revoked_at' => now()]);
        });

        return $temporaryPassword;
    }
}
