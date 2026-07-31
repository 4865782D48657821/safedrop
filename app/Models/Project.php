<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'language',
        'license',
        'publication_status',
        'moderation_status',
        'age_rating',
    ];

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }
}
