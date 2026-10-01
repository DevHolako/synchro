<?php

namespace App\Enums;

enum UnavailabilityStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Statuses that still constrain scheduling (soft conflicts) and block overlapping requests.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Approved];
    }
}
