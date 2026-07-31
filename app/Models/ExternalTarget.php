<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
