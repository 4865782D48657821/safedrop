<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReport extends Model
{
    protected $fillable = [
        'project_id',
        'reporter_id',
        'reporter_email',
        'fingerprint',
        'project_snapshot',
        'reason',
        'details',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'project_snapshot' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
