<?php

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

    'roles' => [
        'guest',
        'member',
        'junior_creator',
        'adult_creator_unverified',
        'adult_creator_verified',
        'advertiser',
        'moderator',
        'administrator',
    ],

    'age_groups' => [
        'JUNIOR',
        'ADULT_UNVERIFIED',
        'ADULT_VERIFIED',
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
