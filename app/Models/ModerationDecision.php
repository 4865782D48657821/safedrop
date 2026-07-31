<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationDecision extends Model
{
    protected $fillable = [
        'moderation_case_id',
        'moderator_id',
        'action',
        'note',
        'moderator_snapshot',
        'status_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'moderator_snapshot' => 'array',
            'status_snapshot' => 'array',
        ];
    }

    public function moderationCase(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
