<?php

namespace App\Models;

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
        'target_type',
        'last_checked_at',
        'reachability_status',
        'trust_status',
    ];

    protected function casts(): array
    {
        return [
            'redirect_chain' => 'array',
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
            'target_type' => $review->targetType,
            'last_checked_at' => now(),
            'reachability_status' => $review->reachabilityStatus,
            'trust_status' => $review->trustStatus,
        ]);
    }
}
