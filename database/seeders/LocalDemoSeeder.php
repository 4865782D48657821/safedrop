<?php

namespace Database\Seeders;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ContentReport;
use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\ProjectInterestFeedback;
use App\Models\ProjectRating;
use App\Models\Release;
use App\Models\RightsCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoSeeder extends Seeder
{
    public const DEMO_ENVIRONMENTS = [
        'local',
        'dev',
        'development',
        'qa',
        'quality-assurance',
        'testing',
    ];

    private const PASSWORD = 'development-only';

    public function run(): void
    {
        if (! app()->environment(self::DEMO_ENVIRONMENTS)) {
            return;
        }

        $member = $this->user('member@safedrop.test', 'Mira Member', UserRole::Member, AgeGroup::Junior);
        $juniorCreator = $this->user('creator@safedrop.test', 'Blocksmith Studio', UserRole::JuniorCreator, AgeGroup::Junior);
        $adultCreator = $this->user(
            'adult-creator@safedrop.test',
            'LuaLaunch',
            UserRole::AdultCreatorVerified,
            AgeGroup::AdultVerified,
            now(),
        );
        $moderator = $this->user('moderator@safedrop.test', 'Morgan Moderator', UserRole::Moderator, AgeGroup::AdultVerified);
        $this->user('admin@safedrop.test', 'Ada Admin', UserRole::Administrator, AgeGroup::AdultVerified);

        [$minecraftProject, $minecraftRelease, $minecraftTarget] = $this->projectWithReleaseAndTarget(
            creator: $juniorCreator,
            project: [
                'slug' => 'skyforge-build-tools',
                'title' => 'SkyForge Build Tools',
                'summary' => 'Server utilities for protected build zones and collaborative survival maps.',
                'description' => 'A moderated Minecraft server plugin starter project for protected build zones.',
                'game' => 'minecraft',
                'project_type' => 'plugin',
                'categories' => ['servers', 'moderation'],
                'tags' => ['servers', 'tools', 'moderated'],
                'language' => 'en',
                'license' => 'custom',
                'publication_status' => 'published',
                'moderation_status' => 'approved',
                'age_rating' => '12+',
            ],
            release: [
                'version' => '1.0.0',
                'changelog' => 'Initial metadata release for the MVP catalog.',
                'compatibility' => [
                    'edition' => 'java',
                    'minecraft_versions' => ['1.21'],
                    'mod_loaders' => ['paper'],
                ],
                'published_at' => now(),
                'moderation_status' => 'approved',
            ],
            target: [
                'original_url' => 'https://modrinth.com/plugin/example',
                'normalized_url' => 'https://modrinth.com/plugin/example',
                'redirect_chain' => ['https://modrinth.com/plugin/example'],
                'target_domain' => 'modrinth.com',
                'domain_status' => DomainStatus::Known,
                'target_type' => 'project_page',
                'last_checked_at' => now(),
                'reachability_status' => 'reachable',
                'trust_status' => 'approved',
            ],
        );

        [$robloxProject, $robloxRelease, $robloxTarget] = $this->projectWithReleaseAndTarget(
            creator: $adultCreator,
            project: [
                'slug' => 'obby-race-kit',
                'title' => 'Obby Race Kit',
                'summary' => 'Starter kit for timing checkpoints, lap ghosts, and mobile-friendly race UI.',
                'description' => 'A Roblox creator resource for fast obby race prototyping.',
                'game' => 'roblox',
                'project_type' => 'resource',
                'categories' => ['templates', 'creator_tools'],
                'tags' => ['templates', 'mobile', 'creator-tools'],
                'language' => 'en',
                'license' => 'custom',
                'publication_status' => 'published',
                'moderation_status' => 'approved',
                'age_rating' => 'teen',
            ],
            release: [
                'version' => '1.0.0',
                'changelog' => 'Initial Roblox resource metadata.',
                'compatibility' => [
                    'supported_devices' => ['desktop', 'mobile'],
                    'access_model' => 'free',
                ],
                'published_at' => now(),
                'moderation_status' => 'approved',
            ],
            target: [
                'original_url' => 'https://create.roblox.com/store/asset/example',
                'normalized_url' => 'https://create.roblox.com/store/asset/example',
                'redirect_chain' => ['https://create.roblox.com/store/asset/example'],
                'target_domain' => 'create.roblox.com',
                'domain_status' => DomainStatus::Known,
                'target_type' => 'project_page',
                'last_checked_at' => now(),
                'reachability_status' => 'reachable',
                'trust_status' => 'approved',
            ],
        );

        $this->resolvedCase($minecraftProject, 'project_metadata', $moderator);
        $this->resolvedCase($minecraftRelease, 'release', $moderator);
        $this->resolvedCase($minecraftTarget, 'external_target', $moderator);
        $this->resolvedCase($robloxProject, 'project_metadata', $moderator);
        $this->resolvedCase($robloxRelease, 'release', $moderator);
        $this->resolvedCase($robloxTarget, 'external_target', $moderator);

        [$pendingProject, $pendingRelease, $pendingTarget] = $this->projectWithReleaseAndTarget(
            creator: $juniorCreator,
            project: [
                'slug' => 'pending-creator-submission',
                'title' => 'Pending Creator Submission',
                'summary' => 'A submitted project that is waiting for moderator review.',
                'description' => 'Use this entry to exercise the moderation queue locally.',
                'game' => 'minecraft',
                'project_type' => 'map',
                'categories' => ['adventure'],
                'tags' => ['pending', 'moderation'],
                'language' => 'en',
                'license' => 'custom',
                'publication_status' => 'published',
                'moderation_status' => 'pending',
                'age_rating' => '12+',
            ],
            release: [
                'version' => '0.1.0',
                'changelog' => 'First creator-submitted release awaiting approval.',
                'compatibility' => ['minecraft_versions' => ['1.21']],
                'published_at' => now(),
                'moderation_status' => 'pending',
            ],
            target: [
                'original_url' => 'https://modrinth.com/project/pending-example',
                'normalized_url' => 'https://modrinth.com/project/pending-example',
                'redirect_chain' => ['https://modrinth.com/project/pending-example'],
                'target_domain' => 'modrinth.com',
                'domain_status' => DomainStatus::New,
                'target_type' => 'project_page',
                'last_checked_at' => now(),
                'reachability_status' => 'reachable',
                'trust_status' => 'pending',
            ],
        );

        ModerationCase::openForSubject($pendingProject, 'project_metadata', 'medium', 'Demo creator project awaits metadata review.');
        ModerationCase::openForSubject($pendingRelease, 'release', 'medium', 'Demo creator release awaits review.');
        ModerationCase::openForSubject($pendingTarget, 'external_target', 'medium', 'Demo external target awaits domain review.');

        $this->report($member, $minecraftProject);
        $this->rightsCase($robloxProject);
        $member->savedProjects()->syncWithoutDetaching([
            $minecraftProject->id,
            $robloxProject->id,
        ]);
        $member->followedCreators()->syncWithoutDetaching([
            $juniorCreator->id,
            $adultCreator->id,
        ]);
        $member->projectRatings()->updateOrCreate(
            ['project_id' => $minecraftProject->id],
            ['signal' => ProjectRating::HELPFUL],
        );
        $member->projectInterestFeedback()->updateOrCreate(
            ['project_id' => $robloxProject->id],
            ['signal' => ProjectInterestFeedback::NOT_INTERESTED],
        );
    }

    private function user(
        string $email,
        string $name,
        UserRole $role,
        AgeGroup $ageGroup,
        mixed $creatorVerifiedAt = null,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
            ],
        );

        $user->forceFill([
            'role' => $role,
            'age_group' => $ageGroup,
            'email_verified_at' => now(),
            'creator_verified_at' => $creatorVerifiedAt,
        ])->save();

        return $user;
    }

    private function projectWithReleaseAndTarget(User $creator, array $project, array $release, array $target): array
    {
        $projectModel = Project::query()->updateOrCreate(
            ['slug' => $project['slug']],
            ['creator_id' => $creator->id] + $project,
        );

        $releaseModel = Release::query()->updateOrCreate(
            [
                'project_id' => $projectModel->id,
                'version' => $release['version'],
            ],
            $release,
        );

        $targetModel = ExternalTarget::query()->updateOrCreate(
            [
                'release_id' => $releaseModel->id,
                'target_domain' => $target['target_domain'],
                'target_type' => $target['target_type'],
            ],
            $target,
        );

        return [$projectModel, $releaseModel, $targetModel];
    }

    private function resolvedCase(Project|Release|ExternalTarget $subject, string $category, User $moderator): void
    {
        ModerationCase::query()->updateOrCreate(
            [
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'category' => $category,
                'status' => 'resolved',
            ],
            [
                'open_key' => null,
                'risk_level' => 'low',
                'reason' => 'Demo content starts approved for local exploration.',
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
            ],
        );
    }

    private function report(User $member, Project $project): void
    {
        $report = ContentReport::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'reason' => 'unsafe_link',
                'reporter_email' => 'parent@safedrop.test',
            ],
            [
                'reporter_id' => $member->id,
                'fingerprint' => "demo-report-{$project->id}-unsafe-link",
                'details' => 'Demo report: please review the external destination copy and domain.',
                'project_snapshot' => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->title,
                ],
                'status' => 'open',
            ],
        );

        ModerationCase::openForSubject($report, 'report', 'medium', 'Demo report for local moderation testing.');
    }

    private function rightsCase(Project $project): void
    {
        $case = RightsCase::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'claimant_email' => 'rights-owner@safedrop.test',
                'claim_type' => 'copyright',
            ],
            [
                'claimant_name' => 'Demo Rights Owner',
                'fingerprint' => "demo-rights-{$project->id}-copyright",
                'details' => 'Demo rights case for exercising rights moderation workflow locally.',
                'status' => 'open',
            ],
        );

        ModerationCase::openForSubject($case, 'rights_case', 'high', 'Demo rights case for local moderation testing.');
    }
}
