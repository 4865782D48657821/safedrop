<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationCase extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'category',
        'status',
        'open_key',
        'risk_level',
        'reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ModerationDecision::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public static function openForSubject(Model $subject, string $category, string $riskLevel, ?string $reason = null): self
    {
        return self::query()->updateOrCreate(
            [
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'category' => $category,
                'status' => 'open',
                'open_key' => self::openKey($subject, $category),
            ],
            [
                'risk_level' => $riskLevel,
                'reason' => $reason,
            ],
        );
    }

    public static function openKey(Model $subject, string $category): string
    {
        return sha1($subject::class.'|'.$subject->getKey().'|'.$category);
    }

    public function subjectLabel(): string
    {
        $subject = $this->subject;

        return match (true) {
            $subject instanceof Project => "Project: {$subject->title}",
            $subject instanceof Release => "Release: {$subject->project?->title} {$subject->version}",
            $subject instanceof ExternalTarget => "External target: {$subject->target_domain}",
            default => class_basename((string) $this->subject_type).' #'.$this->subject_id,
        };
    }
}
