<?php

namespace Database\Seeders;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->firstOrCreate(
            ['email' => 'creator@safedrop.test'],
            [
                'name' => 'Blocksmith Studio',
                'password' => Hash::make('development-only'),
            ],
        );

        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        $this->seedProject(
            creator: $creator,
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

        $this->seedProject(
            creator: $creator,
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
                'age_rating' => '12+',
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
    }

    private function seedProject(User $creator, array $project, array $release, array $target): void
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

        ExternalTarget::query()->updateOrCreate(
            [
                'release_id' => $releaseModel->id,
                'target_domain' => $target['target_domain'],
                'target_type' => $target['target_type'],
            ],
            $target,
        );

        ModerationCase::query()->updateOrCreate(
            [
                'subject_type' => Project::class,
                'subject_id' => $projectModel->id,
                'category' => 'project_metadata',
                'status' => 'resolved',
            ],
            [
                'risk_level' => 'low',
                'reason' => 'Seed project starts approved for local discovery.',
                'reviewed_at' => now(),
            ],
        );
    }
}
