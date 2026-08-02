<?php

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
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

    'onboarding' => [
        'categories' => [
            'adventure',
            'building',
            'creator_tools',
            'moderation',
            'mobile',
            'servers',
            'templates',
            'tools',
        ],
        'versions' => [
            'minecraft:1.21',
            'minecraft:1.20',
            'roblox:latest',
        ],
        'platforms' => [
            'desktop',
            'mobile',
            'console',
            'java',
            'bedrock',
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

    'domain_statuses' => [
        DomainStatus::Trusted->value,
        DomainStatus::Known->value,
        DomainStatus::New->value,
        DomainStatus::Suspicious->value,
        DomainStatus::Blocked->value,
    ],

    'publishable_domain_statuses' => [
        DomainStatus::Trusted->value,
        DomainStatus::Known->value,
    ],

    'public_project_statuses' => [
        'publication' => ['published'],
        'moderation' => ['approved'],
    ],

    'public_release_statuses' => [
        'moderation' => ['approved'],
    ],

    'ad_free_age_ratings' => [
        '12+',
        'under_13',
    ],

    'junior_feed_age_ratings' => [
        '12+',
        'under_13',
    ],

    'moderation_actions' => [
        'approve',
        'needs_review',
        'block',
    ],

    'moderation_case_categories' => [
        'project_metadata',
        'release',
        'external_target',
        'report',
        'rights_case',
    ],

    'report_reasons' => [
        'unsafe_link',
        'inappropriate_content',
        'misleading_metadata',
        'spam_or_abuse',
        'other',
    ],

    'rights_claim_types' => [
        'copyright',
        'trademark',
        'ownership_dispute',
        'impersonation',
        'other',
    ],

    'mvp_external_target_types' => [
        'project_page',
    ],

    'url_review' => [
        'allowed_schemes' => ['http', 'https'],
        'blocked_hosts' => [
            'localhost',
        ],
        'blocked_suffixes' => [
            '.localhost',
            '.local',
            '.internal',
        ],
        'shortener_domains' => [
            'bit.ly',
            'buff.ly',
            'cutt.ly',
            'goo.gl',
            'is.gd',
            'linktr.ee',
            'ow.ly',
            'rebrand.ly',
            'shorturl.at',
            'tinyurl.com',
            't.co',
        ],
    ],

    'redirects' => [
        'signed_url_ttl_minutes' => 10,
        'rate_limit_per_minute' => 30,
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
