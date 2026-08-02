<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_requires_authentication(): void
    {
        $this->get(route('onboarding.edit'))->assertRedirect(route('login'));
        $this->put(route('onboarding.update'))->assertRedirect(route('login'));
        $this->post(route('onboarding.skip'))->assertRedirect(route('login'));
    }

    public function test_user_can_save_onboarding_preferences_for_public_creator(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $creator = $this->creator('known@safedrop.test', 'Known Studio');
        $this->publicProject(['creator_id' => $creator->id]);

        $this->actingAs($user)
            ->put(route('onboarding.update'), [
                'games' => ['minecraft'],
                'project_types' => ['plugin'],
                'categories' => ['servers'],
                'versions' => ['minecraft:1.21'],
                'platforms' => ['java'],
                'creator_ids' => [$creator->id],
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('user_onboarding_preferences', [
            'user_id' => $user->id,
            'skipped_at' => null,
        ]);
        $preference = $user->onboardingPreference()->firstOrFail();
        $this->assertSame(['minecraft'], $preference->games);
        $this->assertSame(['plugin'], $preference->project_types);
        $this->assertSame(['servers'], $preference->categories);
        $this->assertSame(['minecraft:1.21'], $preference->versions);
        $this->assertSame(['java'], $preference->platforms);
        $this->assertSame([$creator->id], $preference->creator_ids);
        $this->assertNotNull($preference->completed_at);
    }

    public function test_user_cannot_save_unknown_or_unavailable_creator(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $hiddenCreator = $this->creator('hidden@safedrop.test', 'Hidden Studio');

        $this->actingAs($user)
            ->from(route('onboarding.edit'))
            ->put(route('onboarding.update'), [
                'games' => ['minecraft'],
                'creator_ids' => [$hiddenCreator->id],
            ])
            ->assertRedirect(route('onboarding.edit'))
            ->assertSessionHasErrors('creator_ids.0');

        $this->assertDatabaseMissing('user_onboarding_preferences', [
            'user_id' => $user->id,
        ]);
    }

    public function test_junior_onboarding_hides_age_ineligible_creators(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $junior = $this->member();
        $adultCreator = $this->creator('adult-creator@safedrop.test', 'Adult Creator');
        $this->publicProject([
            'creator_id' => $adultCreator->id,
            'slug' => 'adult-only-project',
            'title' => 'Adult Only Project',
            'age_rating' => '18+',
        ]);

        $this->actingAs($junior)
            ->get(route('onboarding.edit'))
            ->assertOk()
            ->assertDontSee('Adult Creator');

        $this->actingAs($junior)
            ->from(route('onboarding.edit'))
            ->put(route('onboarding.update'), [
                'creator_ids' => [$adultCreator->id],
            ])
            ->assertRedirect(route('onboarding.edit'))
            ->assertSessionHasErrors('creator_ids.0');
    }

    public function test_user_can_skip_onboarding_and_hide_home_prompt(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $this->publicProject();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Personalize your first feed');

        $this->actingAs($user)
            ->post(route('onboarding.skip'))
            ->assertRedirect(route('home'));

        $this->assertNotNull($user->onboardingPreference()->firstOrFail()->skipped_at);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Personalize your first feed');
    }

    public function test_onboarding_preferences_boost_first_feed_without_bypassing_safety(): void
    {
        $user = $this->member();
        $minecraft = $this->publicProject([
            'slug' => 'minecraft-tools',
            'title' => 'Minecraft Tools',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'categories' => ['servers'],
        ]);
        $robloxCreator = $this->creator('roblox@safedrop.test', 'Roblox Studio');
        $roblox = $this->publicProject([
            'creator_id' => $robloxCreator->id,
            'slug' => 'roblox-kit',
            'title' => 'Roblox Kit',
            'game' => 'roblox',
            'project_type' => 'resource',
            'categories' => ['templates', 'mobile'],
        ], [], ['compatibility' => ['supported_devices' => ['mobile']]]);
        $unsafe = $this->publicProject([
            'slug' => 'unsafe-roblox-kit',
            'title' => 'Unsafe Roblox Kit',
            'game' => 'roblox',
            'project_type' => 'resource',
            'categories' => ['templates'],
        ], ['trust_status' => 'blocked']);
        $minecraft->ratings()->create([
            'user_id' => $this->member('rater@safedrop.test')->id,
            'signal' => 'helpful',
        ]);
        $user->onboardingPreference()->create([
            'games' => ['roblox'],
            'project_types' => ['resource'],
            'categories' => ['templates'],
            'versions' => ['roblox:latest'],
            'platforms' => ['mobile'],
            'creator_ids' => [$robloxCreator->id],
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                $roblox->title,
                $minecraft->title,
            ])
            ->assertDontSee($unsafe->title);
    }

    private function member(string $email = 'member@safedrop.test'): User
    {
        return User::query()->create([
            'name' => 'Member',
            'email' => $email,
            'password' => 'safe-password',
        ]);
    }

    private function creator(string $email = 'creator@safedrop.test', string $name = 'Blocksmith Studio'): User
    {
        $creator = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'safe-password',
            ],
        );
        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        return $creator;
    }

    private function publicProject(array $projectOverrides = [], array $targetOverrides = [], array $releaseOverrides = []): Project
    {
        $slug = $projectOverrides['slug'] ?? 'skyforge-build-tools';

        $project = Project::query()->create(array_merge([
            'creator_id' => $this->creator("creator-{$slug}@safedrop.test")->id,
            'slug' => $slug,
            'title' => 'SkyForge Build Tools',
            'summary' => 'Server utilities for protected build zones and collaborative survival maps.',
            'description' => 'A moderated Minecraft server plugin starter project.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'categories' => ['servers', 'moderation'],
            'tags' => ['servers', 'tools'],
            'language' => 'en',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
            'age_rating' => '12+',
        ], $projectOverrides));

        $release = Release::query()->create(array_merge([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'compatibility' => ['minecraft_versions' => ['1.21'], 'edition' => 'java'],
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
