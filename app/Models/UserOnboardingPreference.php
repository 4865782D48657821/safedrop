<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboardingPreference extends Model
{
    protected $fillable = [
        'user_id',
        'games',
        'project_types',
        'categories',
        'versions',
        'platforms',
        'creator_ids',
        'completed_at',
        'skipped_at',
    ];

    protected function casts(): array
    {
        return [
            'games' => 'array',
            'project_types' => 'array',
            'categories' => 'array',
            'versions' => 'array',
            'platforms' => 'array',
            'creator_ids' => 'array',
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
