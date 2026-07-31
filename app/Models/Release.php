<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $fillable = [
        'project_id',
        'version',
        'changelog',
        'compatibility',
        'published_at',
        'moderation_status',
    ];

    protected function casts(): array
    {
        return [
            'compatibility' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function externalTargets(): HasMany
    {
        return $this->hasMany(ExternalTarget::class);
    }

    public function approvedExternalTargets(): HasMany
    {
        return $this->publicExternalTargets();
    }

    public function publicExternalTargets(): HasMany
    {
        return $this->externalTargets()
            ->where('trust_status', 'approved')
            ->where('reachability_status', 'reachable')
            ->whereIn('domain_status', config('safedrop.publishable_domain_statuses'))
            ->where('target_type', 'project_page');
    }
}
