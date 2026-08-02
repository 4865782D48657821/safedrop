<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'creator_id',
        'notify_new_projects',
        'notify_new_releases',
        'notify_livestreams',
    ];

    protected function casts(): array
    {
        return [
            'notify_new_projects' => 'boolean',
            'notify_new_releases' => 'boolean',
            'notify_livestreams' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
