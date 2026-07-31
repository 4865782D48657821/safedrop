<?php

use App\Enums\AgeGroup;
use App\Enums\UserRole;

return [
    'games' => [
        'minecraft' => [
            'label' => 'Minecraft',
            'project_types' => ['mod', 'modpack', 'resource_pack', 'shader', 'map', 'plugin', 'datapack', 'server', 'tool'],
        ],
        'roblox' => [
            'label' => 'Roblox',
            'project_types' => ['experience', 'asset', 'model', 'plugin', 'development_tool', 'resource', 'tutorial', 'community_project'],
        ],
    ],

    'access_actors' => [
        'guest',
        UserRole::Member->value,
        UserRole::JuniorCreator->value,
        UserRole::AdultCreatorUnverified->value,
        UserRole::AdultCreatorVerified->value,
        UserRole::Advertiser->value,
        UserRole::Moderator->value,
        UserRole::Administrator->value,
    ],

    'roles' => [
        UserRole::Member->value,
        UserRole::JuniorCreator->value,
        UserRole::AdultCreatorUnverified->value,
        UserRole::AdultCreatorVerified->value,
        UserRole::Advertiser->value,
        UserRole::Moderator->value,
        UserRole::Administrator->value,
    ],

    'age_groups' => [
        AgeGroup::Junior->value,
        AgeGroup::AdultUnverified->value,
        AgeGroup::AdultVerified->value,
    ],

    'trust_statuses' => [
        'pending',
        'approved',
        'blocked',
        'needs_review',
    ],

    'seed_projects' => [
        [
            'slug' => 'skyforge-build-tools',
            'title' => 'SkyForge Build Tools',
            'game' => 'Minecraft',
            'type' => 'Plugin',
            'creator' => 'Blocksmith Studio',
            'summary' => 'Server utilities for protected build zones and collaborative survival maps.',
            'language' => 'English',
            'trust_status' => 'approved',
            'external_url' => 'https://modrinth.com/plugin/example',
            'tags' => ['servers', 'tools', 'moderated'],
        ],
        [
            'slug' => 'obby-race-kit',
            'title' => 'Obby Race Kit',
            'game' => 'Roblox',
            'type' => 'Resource',
            'creator' => 'LuaLaunch',
            'summary' => 'Starter kit for timing checkpoints, lap ghosts, and mobile-friendly race UI.',
            'language' => 'English',
            'trust_status' => 'approved',
            'external_url' => 'https://create.roblox.com/store/asset/example',
            'tags' => ['templates', 'mobile', 'creator-tools'],
        ],
    ],
];
