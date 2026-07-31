<?php

namespace App\Enums;

enum DomainStatus: string
{
    case Trusted = 'trusted';
    case Known = 'known';
    case New = 'new';
    case Suspicious = 'suspicious';
    case Blocked = 'blocked';

    public function isPubliclyAccessible(): bool
    {
        return in_array($this, [
            self::Trusted,
            self::Known,
        ], true);
    }
}
