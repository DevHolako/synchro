<?php

namespace App\Exceptions;

use RuntimeException;

class InvitationException extends RuntimeException
{
    public static function invalid(): self
    {
        return new self(__('messages.invitation_invalid'));
    }

    public static function accountAlreadyActive(): self
    {
        return new self(__('messages.invitation_account_already_active'));
    }
}
