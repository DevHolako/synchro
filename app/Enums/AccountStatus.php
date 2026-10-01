<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
}
