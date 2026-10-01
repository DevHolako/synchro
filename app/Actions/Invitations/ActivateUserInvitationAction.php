<?php

namespace App\Actions\Invitations;

use App\Enums\AccountStatus;
use App\Exceptions\InvitationException;
use App\Models\InvitationToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActivateUserInvitationAction
{
    /**
     * Set the user's initial password, consume the token, and activate the account.
     *
     * @throws InvitationException
     */
    public function execute(InvitationToken $invitation, string $password): User
    {
        return DB::transaction(function () use ($invitation, $password): User {
            /** @var InvitationToken|null $locked */
            $locked = InvitationToken::query()->lockForUpdate()->find($invitation->id);

            if ($locked === null || ! $locked->isUsable()) {
                throw InvitationException::invalid();
            }

            $user = $locked->user;

            if ($user->isActive()) {
                throw InvitationException::invalid();
            }

            $user->forceFill([
                'password' => $password,
                'status' => AccountStatus::Active,
                'activated_at' => now(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $locked->update(['consumed_at' => now()]);

            $user->invitationTokens()->pending()->update(['revoked_at' => now()]);

            return $user;
        });
    }
}
