<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'creator_id',
        'slug',
        'title',
        'summary',
        'description',
        'game',
        'project_type',
        'categories',
        'tags',
        'language',
        'license',
        'publication_status',
        'moderation_status',
        'age_rating',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'tags' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function latestPublicRelease()
    {
        return $this->hasOne(Release::class)
            ->publiclyExposable()
            ->latestOfMany('published_at');
    }

    public function scopePubliclyVisible($query)
    {
        return $query
            ->whereIn('publication_status', config('safedrop.public_project_statuses.publication'))
            ->whereIn('moderation_status', config('safedrop.public_project_statuses.moderation'));
    }
}
