<?php

namespace App\Enums;

/**
 * An invigilator's role in an exam room: one lead (principal) per room, any number of assistants.
 */
enum InvigilatorRole: string
{
    case Principal = 'principal';
    case Adjoint = 'adjoint';
}
