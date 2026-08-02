<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\MemberNotification;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\MemberNotificationService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_notification_preferences_for_followed_creator(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();
        $user->followedCreators()->syncWithoutDetaching([$project->creator_id]);

        $this->actingAs($user)
            ->put(route('creator-notification-preferences.update', $project->creator_id), [
                'notify_new_projects' => '1',
                'notify_new_releases' => '0',
                'notify_livestreams' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('creator_notification_preferences', [
            'user_id' => $user->id,
            'creator_id' => $project->creator_id,
            'notify_new_projects' => true,
            'notify_new_releases' => false,
            'notify_livestreams' => true,
        ]);
    }

    public function test_user_cannot_update_notification_preferences_for_unfollowed_creator(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        $this->actingAs($user)
            ->put(route('creator-notification-preferences.update', $project->creator_id), [
                'notify_new_projects' => '1',
                'notify_new_releases' => '1',
                'notify_livestreams' => '1',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('creator_notification_preferences', [
            'user_id' => $user->id,
            'creator_id' => $project->creator_id,
        ]);
    }

    public function test_project_notifications_are_deduplicated_and_respect_preferences(): void
    {
        $user = $this->member();
        $project = $this->publicProject();
        $user->followedCreators()->syncWithoutDetaching([$project->creator_id]);

        $service = app(MemberNotificationService::class);

        $this->assertSame(1, $service->notifyFollowersForProject($project));
        $this->assertSame(0, $service->notifyFollowersForProject($project));

        $this->assertSame(1, $user->memberNotifications()->where('event_type', MemberNotification::NEW_PROJECT)->count());

        $user->creatorNotificationPreferences()->updateOrCreate(
            ['creator_id' => $project->creator_id],
            [
                'notify_new_projects' => false,
                'notify_new_releases' => true,
                'notify_livestreams' => true,
            ],
        );
        $otherProject = $this->publicProject([
            'slug' => 'other-project',
            'title' => 'Other Project',
            'creator_id' => $project->creator_id,
        ]);

        $this->assertSame(0, $service->notifyFollowersForProject($otherProject));
        $this->assertDatabaseMissing('member_notifications', [
            'user_id' => $user->id,
            'project_id' => $otherProject->id,
        ]);
    }

    public function test_moderation_approval_creates_notification_only_for_safe_visible_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $moderator = $this->moderator();
        $project = $this->publicProject([
            'slug' => 'pending-project',
            'title' => 'Pending Project',
            'moderation_status' => 'pending',
        ]);
        $user->followedCreators()->syncWithoutDetaching([$project->creator_id]);
        $case = ModerationCase::openForSubject($project, 'project_metadata', 'medium', 'Approve this project.');

        $this->actingAs($moderator)
            ->post(route('moderation.decide', $case->id), ['action' => 'approve'])
            ->assertRedirect(route('moderation.index'));

        $this->assertDatabaseHas('member_notifications', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'event_type' => MemberNotification::NEW_PROJECT,
        ]);

        $unsafeProject = $this->publicProject(
            [
                'slug' => 'unsafe-project',
                'title' => 'Unsafe Project',
                'creator_id' => $project->creator_id,
            ],
            ['trust_status' => 'blocked'],
        );

        $this->assertSame(0, app(MemberNotificationService::class)->notifyFollowersForProject($unsafeProject));
        $this->assertDatabaseMissing('member_notifications', [
            'user_id' => $user->id,
            'project_id' => $unsafeProject->id,
        ]);
    }

    public function test_notification_center_lists_visible_notifications_and_marks_read(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();
        $notification = $user->memberNotifications()->create([
            'creator_id' => $project->creator_id,
            'project_id' => $project->id,
            'event_type' => MemberNotification::NEW_PROJECT,
            'dedupe_key' => "project:{$project->id}",
            'title' => 'New project from Blocksmith Studio',
            'body' => $project->title,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('New project from Blocksmith Studio')
            ->assertSee('SkyForge Build Tools')
            ->assertSee('Unread');

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);

        $project->latestPublicRelease->publicExternalTargets()->update(['trust_status' => 'blocked']);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('SkyForge Build Tools')
            ->assertSee('No notifications yet');

        $project = $this->publicProject([
            'slug' => 'visible-project',
            'title' => 'Visible Project',
        ]);
        $notification->forceFill([
            'project_id' => $project->id,
            'dedupe_key' => "project:{$project->id}",
            'title' => 'Visible notification',
            'body' => $project->title,
            'read_at' => null,
        ])->save();
        $project->forceFill(['moderation_status' => 'blocked'])->save();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Visible Project')
            ->assertSee('No notifications yet');
    }

    public function test_live_session_notifications_are_deduplicated(): void
    {
        $user = $this->member();
        $creator = $this->creator('live-creator');
        $user->followedCreators()->syncWithoutDetaching([$creator->id]);

        $service = app(MemberNotificationService::class);

        $this->assertSame(1, $service->notifyFollowersForLiveSession($creator, 'stream-123', 'Building with viewers'));
        $this->assertSame(0, $service->notifyFollowersForLiveSession($creator, 'stream-123', 'Building with viewers'));

        $this->assertDatabaseHas('member_notifications', [
            'user_id' => $user->id,
            'creator_id' => $creator->id,
            'event_type' => MemberNotification::LIVE_STREAM,
            'dedupe_key' => "live:{$creator->id}:stream-123",
        ]);
        $this->assertSame(1, $user->memberNotifications()->where('event_type', MemberNotification::LIVE_STREAM)->count());
    }

    public function test_notification_center_hides_blocked_release_notifications(): void
    {
        $user = $this->member();
        $project = $this->publicProject();
        $release = $project->latestPublicRelease;
        $user->memberNotifications()->create([
            'creator_id' => $project->creator_id,
            'project_id' => $project->id,
            'release_id' => $release->id,
            'event_type' => MemberNotification::NEW_RELEASE,
            'dedupe_key' => "release:{$release->id}",
            'title' => 'New release from Blocksmith Studio',
            'body' => "{$project->title} {$release->version}",
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('SkyForge Build Tools 1.0.0');

        $release->forceFill(['moderation_status' => 'rejected'])->save();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('SkyForge Build Tools 1.0.0')
            ->assertSee('No notifications yet');
    }

    public function test_external_target_approval_notifies_release_when_project_is_already_visible(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $moderator = $this->moderator();
        $project = $this->publicProject();
        $user->followedCreators()->syncWithoutDetaching([$project->creator_id]);
        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.1.0',
            'published_at' => now()->addMinute(),
            'moderation_status' => 'approved',
        ]);
        $target = ExternalTarget::query()->create([
            'release_id' => $release->id,
            'original_url' => 'https://modrinth.com/plugin/example-v2',
            'normalized_url' => 'https://modrinth.com/plugin/example-v2',
            'redirect_chain' => ['https://modrinth.com/plugin/example-v2'],
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::New,
            'target_type' => 'project_page',
            'last_checked_at' => now(),
            'reachability_status' => 'reachable',
            'trust_status' => 'needs_review',
        ]);
        $case = ModerationCase::openForSubject($target, 'external_target', 'medium', 'Approve target.');

        $this->actingAs($moderator)
            ->post(route('moderation.decide', $case->id), ['action' => 'approve'])
            ->assertRedirect(route('moderation.index'));

        $this->assertDatabaseHas('member_notifications', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'release_id' => $release->id,
            'event_type' => MemberNotification::NEW_RELEASE,
            'dedupe_key' => "release:{$release->id}",
        ]);
        $this->assertDatabaseMissing('member_notifications', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'event_type' => MemberNotification::NEW_PROJECT,
            'dedupe_key' => "project:{$project->id}",
        ]);
    }

    private function member(string $email = 'member@safedrop.test'): User
    {
        return User::query()->create([
            'name' => 'Member',
            'email' => $email,
            'password' => 'safe-password',
        ]);
    }

    private function moderator(): User
    {
        $user = $this->member('moderator@safedrop.test');
        $user->forceFill([
            'role' => UserRole::Moderator,
            'age_group' => AgeGroup::AdultVerified,
        ])->save();

        return $user;
    }

    private function creator(string $slug = 'skyforge-build-tools'): User
    {
        $creator = User::query()->create([
            'name' => "Creator {$slug}",
            'email' => "creator-{$slug}@safedrop.test",
            'password' => 'safe-password',
        ]);

        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        return $creator;
    }

    private function publicProject(array $overrides = [], array $targetOverrides = [], array $releaseOverrides = []): Project
    {
        $attributes = array_merge([
            'slug' => 'skyforge-build-tools',
            'title' => 'SkyForge Build Tools',
            'summary' => 'Server utilities for protected build zones and collaborative survival maps.',
            'description' => 'A moderated Minecraft server plugin starter project.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'tags' => ['servers', 'tools'],
            'language' => 'en',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
            'age_rating' => '12+',
        ], $overrides);

        $project = Project::query()->create([
            'creator_id' => $attributes['creator_id'] ?? $this->creator($attributes['slug'])->id,
        ] + $attributes);

        $release = Release::query()->create(array_merge([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'published_at' => now(),
            'moderation_status' => 'approved',
        ], $releaseOverrides));

        ExternalTarget::query()->create(array_merge([
            'release_id' => $release->id,
            'original_url' => 'https://modrinth.com/plugin/example',
            'normalized_url' => 'https://modrinth.com/plugin/example',
            'redirect_chain' => ['https://modrinth.com/plugin/example'],
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'last_checked_at' => now(),
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ], $targetOverrides));

        return $project;
    }
}
