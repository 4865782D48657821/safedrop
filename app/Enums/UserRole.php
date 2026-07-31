<?php

namespace App\Enums;

enum UserRole: string
{
    case Member = 'member';
    case JuniorCreator = 'junior_creator';
    case AdultCreatorUnverified = 'adult_creator_unverified';
    case AdultCreatorVerified = 'adult_creator_verified';
    case Advertiser = 'advertiser';
    case Moderator = 'moderator';
    case Administrator = 'administrator';

    public function isCreator(): bool
    {
        return in_array($this, [
            self::JuniorCreator,
            self::AdultCreatorUnverified,
            self::AdultCreatorVerified,
        ], true);
    }

    public function canModerate(): bool
    {
        return in_array($this, [
            self::Moderator,
            self::Administrator,
        ], true);
    }

    public function canAdminister(): bool
    {
        return $this === self::Administrator;
    }
}
