<?php

namespace App\Enums;

enum AgeGroup: string
{
    case Junior = 'JUNIOR';
    case AdultUnverified = 'ADULT_UNVERIFIED';
    case AdultVerified = 'ADULT_VERIFIED';

    public function isAdult(): bool
    {
        return $this !== self::Junior;
    }

    public function isVerifiedAdult(): bool
    {
        return $this === self::AdultVerified;
    }
}
