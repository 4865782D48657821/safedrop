<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LocalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_demo_data_for_local_like_environments(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas(User::class, [
            'email' => 'creator@safedrop.test',
            'role' => UserRole::JuniorCreator->value,
        ]);
        $this->assertDatabaseHas(User::class, [
            'email' => 'moderator@safedrop.test',
            'role' => UserRole::Moderator->value,
        ]);
        $this->assertDatabaseHas(Project::class, [
            'slug' => 'skyforge-build-tools',
            'moderation_status' => 'approved',
        ]);
        $this->assertDatabaseHas(Project::class, [
            'slug' => 'pending-creator-submission',
            'moderation_status' => 'pending',
        ]);
        $this->assertGreaterThanOrEqual(1, Project::query()->publiclyVisible()->count());
        $this->assertGreaterThanOrEqual(1, ModerationCase::query()->where('status', 'open')->count());

        $this->actingAs(User::query()->where('email', 'moderator@safedrop.test')->firstOrFail())
            ->get(route('moderation.index'))
            ->assertOk()
            ->assertSee('Pending Creator Submission')
            ->assertSee('Demo report')
            ->assertSee('Rights case: copyright');
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $firstCounts = [
            'users' => User::query()->count(),
            'projects' => Project::query()->count(),
            'open_cases' => ModerationCase::query()->where('status', 'open')->count(),
        ];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($firstCounts['users'], User::query()->count());
        $this->assertSame($firstCounts['projects'], Project::query()->count());
        $this->assertSame($firstCounts['open_cases'], ModerationCase::query()->where('status', 'open')->count());
    }

    public function test_database_seeder_does_not_create_demo_data_in_production_environment(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        app(DatabaseSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('moderation_cases', 0);
    }

    public function test_local_demo_seeder_does_not_create_demo_data_when_called_directly_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        app(LocalDemoSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('moderation_cases', 0);
    }
}
