<?php

namespace App\Models;

use App\Enums\DomainStatus;
use App\Services\UrlReviewService;
use App\Support\UrlReviewResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ExternalTarget extends Model
{
    protected $fillable = [
        'release_id',
        'original_url',
        'normalized_url',
        'redirect_chain',
        'target_domain',
        'domain_status',
        'target_type',
        'last_checked_at',
        'reachability_status',
        'trust_status',
    ];

    protected function casts(): array
    {
        return [
            'redirect_chain' => 'array',
            'domain_status' => DomainStatus::class,
            'last_checked_at' => 'datetime',
        ];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function safeDestinationUrl(): ?string
    {
        $url = $this->normalized_url ?: $this->original_url;
        $review = app(UrlReviewService::class)->review($url, $this->target_type ?: 'project_page');

        if ($review->isBlocked()) {
            return null;
        }

        return $url;
    }

    public function publicDestinationUrl(): ?string
    {
        if (! $this->isPubliclyAccessible()) {
            return null;
        }

        $url = $this->safeDestinationUrl();

        if ($url === null) {
            return null;
        }

        $review = app(UrlReviewService::class)->review($url, $this->target_type ?: 'project_page');

        if ($review->reachabilityStatus !== 'reachable') {
            return null;
        }

        if ($review->signals !== []) {
            return null;
        }

        if ($review->targetDomain !== $this->targetDomain()) {
            return null;
        }

        return $url;
    }

    public function isPubliclyAccessible(): bool
    {
        if ($this->trust_status !== 'approved') {
            return false;
        }

        if ($this->reachability_status !== 'reachable') {
            return false;
        }

        if ($this->target_type !== 'project_page') {
            return false;
        }

        return $this->domainStatus()->isPubliclyAccessible();
    }

    public function effectiveDomainStatus(): string
    {
        if ($this->reachability_status === 'unreachable') {
            return 'unreachable';
        }

        return $this->domainStatus()->value;
    }

    public static function makeFromReview(Release $release, UrlReviewResult $review): self
    {
        if ($review->isBlocked() || $review->targetDomain === null) {
            throw new InvalidArgumentException('Blocked URL reviews cannot be stored as external targets.');
        }

        return new self([
            'release_id' => $release->id,
            'original_url' => $review->originalUrl,
            'normalized_url' => $review->normalizedUrl,
            'redirect_chain' => $review->redirectChain,
            'target_domain' => $review->targetDomain,
            'domain_status' => self::domainStatusFromReview($review),
            'target_type' => $review->targetType,
            'last_checked_at' => now(),
            'reachability_status' => $review->reachabilityStatus,
            'trust_status' => $review->trustStatus,
        ]);
    }

    private function domainStatus(): DomainStatus
    {
        if ($this->domain_status instanceof DomainStatus) {
            return $this->domain_status;
        }

        return DomainStatus::tryFrom((string) $this->domain_status) ?? DomainStatus::New;
    }

    private function targetDomain(): string
    {
        return strtolower(rtrim((string) $this->target_domain, '.'));
    }

    private static function domainStatusFromReview(UrlReviewResult $review): DomainStatus
    {
        if ($review->trustStatus === 'blocked') {
            return DomainStatus::Blocked;
        }

        if ($review->signals !== []) {
            return DomainStatus::Suspicious;
        }

        return DomainStatus::New;
    }
}
