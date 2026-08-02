<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInterestFeedback extends Model
{
    public const NOT_INTERESTED = 'not_interested';

    protected $table = 'project_interest_feedback';

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
