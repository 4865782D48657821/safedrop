<?php

namespace App\Support;

final readonly class UrlReviewResult
{
    public function __construct(
        public string $originalUrl,
        public ?string $normalizedUrl,
        public array $redirectChain,
        public ?string $targetDomain,
        public string $targetType,
        public string $reachabilityStatus,
        public string $trustStatus,
        public array $signals = [],
    ) {}

    public function isBlocked(): bool
    {
        return $this->trustStatus === 'blocked';
    }
}
