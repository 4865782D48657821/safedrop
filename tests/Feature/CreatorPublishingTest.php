<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_submit_project_release_and_external_target_for_moderation(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $creator = $this->creator();

        $response = $this->actingAs($creator)->post(route('creator.projects.store'), [
            'title' => 'Sky Forge Toolkit',
            'summary' => 'A concise publishing test project for moderated external destinations.',
            'description' => 'Creator controlled project metadata enters moderation before public discovery.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'tags' => 'Servers, Safety, servers',
            'version' => '1.0.0',
            'external_url' => 'HTTPS://Modrinth.Com/plugin/example#reviews',
        ]);

        $response->assertRedirect(route('creator.dashboard'));
        $response->assertSessionHasNoErrors();

        $project = Project::query()
            ->with('releases.externalTargets')
            ->where('slug', 'sky-forge-toolkit')
            ->firstOrFail();

        $this->assertSame($creator->id, $project->creator_id);
        $this->assertSame('published', $project->publication_status);
        $this->assertSame('pending', $project->moderation_status);
        $this->assertSame(['servers', 'safety'], $project->tags);
        $this->assertNull(Project::query()->publiclyVisible()->whereKey($project->id)->first());

        $release = $project->releases->first();
        $this->assertSame('1.0.0', $release->version);
        $this->assertNotNull($release->published_at);
        $this->assertSame('pending', $release->moderation_status);

        $target = $release->externalTargets->first();
        $this->assertSame('https://modrinth.com/plugin/example', $target->normalized_url);
        $this->assertSame(['https://modrinth.com/plugin/example'], $target->redirect_chain);
        $this->assertSame('modrinth.com', $target->target_domain);
        $this->assertSame(DomainStatus::New, $target->domain_status);
        $this->assertSame('reachable', $target->reachability_status);
        $this->assertSame('pending', $target->trust_status);

        $this->assertDatabaseHas(ModerationCase::class, [
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'category' => 'project_metadata',
            'risk_level' => 'medium',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas(ModerationCase::class, [
            'subject_type' => Release::class,
            'subject_id' => $release->id,
            'category' => 'release',
            'risk_level' => 'medium',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas(ModerationCase::class, [
            'subject_type' => ExternalTarget::class,
            'subject_id' => $target->id,
            'category' => 'external_target',
            'risk_level' => 'medium',
            'status' => 'open',
        ]);

        $this->actingAs($this->moderator())
            ->get(route('moderation.index'))
            ->assertOk()
            ->assertSee('Project: Sky Forge Toolkit')
            ->assertSee('Release: Sky Forge Toolkit 1.0.0')
            ->assertSee('External target: modrinth.com');
    }

    public function test_project_submission_requires_creator_role(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $member = User::query()->create([
            'name' => 'Member',
            'email' => 'member@safedrop.test',
            'password' => 'safe-password',
        ]);

        $this->actingAs($member)->get(route('creator.projects.create'))->assertForbidden();
        $this->actingAs($member)->post(route('creator.projects.store'), [])->assertForbidden();
    }

    public function test_project_submission_validates_game_type_pair(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $response = $this->actingAs($this->creator())->from(route('creator.projects.create'))->post(route('creator.projects.store'), [
            'title' => 'Unsafe Project',
            'summary' => 'A concise publishing test project for validation.',
            'game' => 'minecraft',
            'project_type' => 'experience',
            'version' => '1.0.0',
            'external_url' => 'https://modrinth.com/plugin/example',
        ]);

        $response->assertRedirect(route('creator.projects.create'));
        $response->assertSessionHasErrors(['project_type']);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('releases', 0);
        $this->assertDatabaseCount('external_targets', 0);
    }

    public function test_project_submission_rejects_non_scalar_game_input(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $response = $this->actingAs($this->creator())->from(route('creator.projects.create'))->post(route('creator.projects.store'), [
            'title' => 'Invalid Game Shape',
            'summary' => 'A concise publishing test project for validation.',
            'game' => ['minecraft'],
            'project_type' => 'plugin',
            'version' => '1.0.0',
            'external_url' => 'https://modrinth.com/plugin/example',
        ]);

        $response->assertRedirect(route('creator.projects.create'));
        $response->assertSessionHasErrors(['game', 'project_type']);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('releases', 0);
        $this->assertDatabaseCount('external_targets', 0);
    }

    public function test_project_submission_blocks_unsafe_external_url(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $response = $this->actingAs($this->creator())->from(route('creator.projects.create'))->post(route('creator.projects.store'), [
            'title' => 'Unsafe Project',
            'summary' => 'A concise publishing test project for validation.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'version' => '1.0.0',
            'external_url' => 'https://127.0.0.1/project',
        ]);

        $response->assertRedirect(route('creator.projects.create'));
        $response->assertSessionHasErrors(['external_url']);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('releases', 0);
        $this->assertDatabaseCount('external_targets', 0);
    }

    public function test_project_submission_generates_unique_slugs(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $creator = $this->creator();
        Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'sky-forge-toolkit',
            'title' => 'Sky Forge Toolkit',
            'summary' => 'Existing project.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
        ]);

        $this->actingAs($creator)->post(route('creator.projects.store'), [
            'title' => 'Sky Forge Toolkit',
            'summary' => 'A second project with the same generated slug base.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'version' => '1.0.0',
            'external_url' => 'https://modrinth.com/plugin/another',
        ])->assertRedirect(route('creator.dashboard'));

        $this->assertDatabaseHas('projects', ['slug' => 'sky-forge-toolkit-2']);
    }

    private function creator(): User
    {
        $creator = User::query()->create([
            'name' => 'Junior Creator',
            'email' => 'creator@safedrop.test',
            'password' => 'safe-password',
        ]);

        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        return $creator;
    }

    private function moderator(): User
    {
        $moderator = User::query()->create([
            'name' => 'Moderator',
            'email' => 'moderator@safedrop.test',
            'password' => 'safe-password',
        ]);

        $moderator->forceFill([
            'role' => UserRole::Moderator,
            'age_group' => AgeGroup::AdultVerified,
        ])->save();

        return $moderator;
    }
}
