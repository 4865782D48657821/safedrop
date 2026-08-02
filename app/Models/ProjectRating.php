<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRating extends Model
{
    public const HELPFUL = 'helpful';

    public const NOT_HELPFUL = 'not_helpful';

    public const SIGNALS = [
        self::HELPFUL,
        self::NOT_HELPFUL,
    ];

    protected $fillable = [
        'user_id',
        'project_id',
        'signal',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
