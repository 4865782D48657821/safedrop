<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RightsCase extends Model
{
    protected $fillable = [
        'project_id',
        'claimant_name',
        'claimant_email',
        'fingerprint',
        'claim_type',
        'details',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
