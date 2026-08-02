<?php

namespace Tests\Unit;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\TrustSafetyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustSafetyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_and_approved_projects_are_discoverable(): void
    {
        $policy = app(TrustSafetyPolicy::class);

        $this->assertTrue($policy->canDiscoverProject(new Project([
            'publication_status' => 'published',
            'moderation_status' => 'approved',
        ])));
        $this->assertFalse($policy->canDiscoverProject(new Project([
            'publication_status' => 'draft',
            'moderation_status' => 'approved',
        ])));
        $this->assertFalse($policy->canDiscoverProject(new Project([
            'publication_status' => 'published',
            'moderation_status' => 'rejected',
        ])));
    }

    public function test_project_policy_and_scope_share_configured_statuses(): void
    {
        config()->set('safedrop.public_project_statuses.publication', ['published', 'listed']);
        config()->set('safedrop.public_project_statuses.moderation', ['approved', 'trusted']);

        $creator = User::query()->create([
            'name' => 'Creator',
            'email' => 'policy-scope-creator@safedrop.test',
            'password' => 'safe-password',
        ]);
        $project = Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'policy-scope-project',
            'title' => 'Policy Scope Project',
            'summary' => 'A project using expanded visibility config.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'publication_status' => 'listed',
            'moderation_status' => 'trusted',
        ]);

        $this->assertTrue(app(TrustSafetyPolicy::class)->canDiscoverProject($project));
        $this->assertTrue(Project::query()->publiclyVisible()->whereKey($project->id)->exists());
    }

    public function test_only_published_and_approved_releases_are_exposable(): void
    {
        $policy = app(TrustSafetyPolicy::class);

        $this->assertTrue($policy->canExposeRelease(new Release([
            'published_at' => now(),
            'moderation_status' => 'approved',
        ])));
        $this->assertFalse($policy->canExposeRelease(new Release([
            'published_at' => null,
            'moderation_status' => 'approved',
        ])));
        $this->assertFalse($policy->canExposeRelease(new Release([
            'published_at' => now(),
            'moderation_status' => 'pending',
        ])));
    }

    public function test_release_policy_and_latest_relation_share_configured_statuses(): void
    {
        config()->set('safedrop.public_release_statuses.moderation', ['approved', 'trusted']);

        $creator = User::query()->create([
            'name' => 'Creator',
            'email' => 'release-policy-creator@safedrop.test',
            'password' => 'safe-password',
        ]);
        $project = Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'release-policy-project',
            'title' => 'Release Policy Project',
            'summary' => 'A project using expanded release config.',
            'game' => 'roblox',
            'project_type' => 'resource',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
        ]);
        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'published_at' => now(),
            'moderation_status' => 'trusted',
        ]);

        $this->assertTrue(app(TrustSafetyPolicy::class)->canExposeRelease($release));
        $this->assertTrue(Release::query()->publiclyExposable()->whereKey($release->id)->exists());
        $this->assertSame($release->id, $project->fresh()->latestPublicRelease->id);
    }

    public function test_redirect_policy_blocks_unsafe_or_untrusted_targets(): void
    {
        $policy = app(TrustSafetyPolicy::class);

        $approved = $this->target([
            'normalized_url' => 'https://modrinth.com/plugin/example',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);
        $blockedDomain = $this->target([
            'normalized_url' => 'https://modrinth.com/plugin/example',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Blocked,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);
        $unsafeUrl = $this->target([
            'normalized_url' => 'https://127.0.0.1/project',
            'target_domain' => '127.0.0.1',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertTrue($policy->canRedirectToTarget($approved));
        $this->assertFalse($policy->canRedirectToTarget($blockedDomain));
        $this->assertFalse($policy->canRedirectToTarget($unsafeUrl));
    }

    public function test_feed_target_policy_matches_redirect_safety_contract(): void
    {
        $policy = app(TrustSafetyPolicy::class);

        $approved = $this->target([
            'original_url' => 'https://modrinth.com/plugin/example',
            'normalized_url' => 'https://modrinth.com/plugin/example',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);
        $nonHttps = $this->target([
            'original_url' => 'http://modrinth.com/plugin/example',
            'normalized_url' => 'http://modrinth.com/plugin/example',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);
        $shortener = $this->target([
            'original_url' => 'https://bit.ly/example',
            'normalized_url' => 'https://bit.ly/example',
            'target_domain' => 'bit.ly',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);
        $unsafeScheme = $this->target([
            'original_url' => 'javascript:alert(1)',
            'normalized_url' => 'javascript:alert(1)',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);
        $domainDrift = $this->target([
            'original_url' => 'https://evil.example/project',
            'normalized_url' => 'https://evil.example/project',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertTrue($policy->canListTargetInFeed($approved));
        $this->assertFalse($policy->canListTargetInFeed($nonHttps));
        $this->assertFalse($policy->canListTargetInFeed($shortener));
        $this->assertFalse($policy->canListTargetInFeed($unsafeScheme));
        $this->assertFalse($policy->canListTargetInFeed($domainDrift));
    }

    public function test_revenue_ads_require_verified_adult_creator_and_non_junior_age_rating(): void
    {
        $policy = app(TrustSafetyPolicy::class);

        $juniorCreator = $this->user(UserRole::JuniorCreator, AgeGroup::Junior);
        $verifiedCreator = $this->user(UserRole::AdultCreatorVerified, AgeGroup::AdultVerified, now());

        $juniorProject = $this->project($juniorCreator, '12+');
        $adultProject = $this->project($verifiedCreator, '18+');
        $adultJuniorRatedProject = $this->project($verifiedCreator, '12+');
        $unapprovedProject = $this->project($verifiedCreator, '18+', moderationStatus: 'pending');

        $this->assertFalse($policy->canShowRevenueAdsOnProject($juniorProject));
        $this->assertTrue($policy->canShowRevenueAdsOnProject($adultProject));
        $this->assertFalse($policy->canShowRevenueAdsOnProject($adultJuniorRatedProject));
        $this->assertFalse($policy->canShowRevenueAdsOnProject($unapprovedProject));
    }

    private function target(array $attributes): ExternalTarget
    {
        return new ExternalTarget(array_merge([
            'original_url' => $attributes['normalized_url'] ?? 'https://example.com/project',
            'target_type' => 'project_page',
        ], $attributes));
    }

    private function user(UserRole $role, AgeGroup $ageGroup, mixed $verifiedAt = null): User
    {
        return (new User)->forceFill([
            'role' => $role,
            'age_group' => $ageGroup,
            'creator_verified_at' => $verifiedAt,
        ]);
    }

    private function project(User $creator, string $ageRating, string $moderationStatus = 'approved'): Project
    {
        $project = new Project([
            'publication_status' => 'published',
            'moderation_status' => $moderationStatus,
            'age_rating' => $ageRating,
        ]);
        $project->setRelation('creator', $creator);

        return $project;
    }
}
