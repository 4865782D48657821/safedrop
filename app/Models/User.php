<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $attributes = [
        'role' => 'member',
        'age_group' => 'JUNIOR',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isCreator(): bool
    {
        return $this->role->isCreator();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'creator_id');
    }

    public function savedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'saved_projects')->withPivot('id')->withTimestamps();
    }

    public function followedCreators(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'creator_follows', 'follower_id', 'creator_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function followerUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'creator_follows', 'creator_id', 'follower_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function projectRatings(): HasMany
    {
        return $this->hasMany(ProjectRating::class);
    }

    public function projectInterestFeedback(): HasMany
    {
        return $this->hasMany(ProjectInterestFeedback::class);
    }

    public function canPublishProjects(): bool
    {
        return $this->isCreator();
    }

    public function canModerateContent(): bool
    {
        return $this->role->canModerate();
    }

    public function canAdministerPlatform(): bool
    {
        return $this->role->canAdminister();
    }

    public function canMonetizeProjects(): bool
    {
        return $this->role === UserRole::AdultCreatorVerified
            && $this->age_group->isVerifiedAdult()
            && $this->creator_verified_at !== null;
    }

    public function canManagePayouts(): bool
    {
        return $this->canMonetizeProjects();
    }

    public function canShowRevenueAdsOnProjectPages(): bool
    {
        return $this->canMonetizeProjects();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'creator_verified_at' => 'datetime',
            'role' => UserRole::class,
            'age_group' => AgeGroup::class,
            'password' => 'hashed',
        ];
    }
}
