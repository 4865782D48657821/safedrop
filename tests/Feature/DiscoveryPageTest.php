<?php

namespace Tests\Feature;

use Tests\TestCase;

class DiscoveryPageTest extends TestCase
{
    public function test_homepage_shows_mvp_discovery_projects(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Safedrop');
        $response->assertSee('Minecraft');
        $response->assertSee('Roblox');
        $response->assertSee('SkyForge Build Tools');
    }

    public function test_project_page_exposes_reviewed_external_destination(): void
    {
        $response = $this->get('/projects/skyforge-build-tools');

        $response->assertOk();
        $response->assertSee('Safety Status');
        $response->assertSee('modrinth.com');
    }
}
