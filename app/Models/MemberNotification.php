<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNotification extends Model
{
    public const NEW_PROJECT = 'new_project';

    public const NEW_RELEASE = 'new_release';

    public const LIVE_STREAM = 'live_stream';

    protected $fillable = [
        'user_id',
        'creator_id',
        'project_id',
        'release_id',
        'event_type',
        'dedupe_key',
        'title',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
