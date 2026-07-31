<?php

namespace Tests\Unit;

use App\Enums\AgeGroup;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserRolePolicyTest extends TestCase
{
    public function test_junior_creator_can_publish_but_cannot_monetize_or_manage_payouts(): void
    {
        $user = (new User)->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ]);

        $this->assertTrue($user->canPublishProjects());
        $this->assertFalse($user->canMonetizeProjects());
        $this->assertFalse($user->canManagePayouts());
        $this->assertFalse($user->canShowRevenueAdsOnProjectPages());
    }

    public function test_adult_creator_without_verification_cannot_monetize(): void
    {
        $user = (new User)->forceFill([
            'role' => UserRole::AdultCreatorUnverified,
            'age_group' => AgeGroup::AdultUnverified,
        ]);

        $this->assertTrue($user->canPublishProjects());
        $this->assertFalse($user->canMonetizeProjects());
    }

    public function test_verified_adult_creator_requires_role_age_group_and_verification_timestamp(): void
    {
        $unverifiedTimestamp = (new User)->forceFill([
            'role' => UserRole::AdultCreatorVerified,
            'age_group' => AgeGroup::AdultVerified,
        ]);

        $fullyVerified = (new User)->forceFill([
            'role' => UserRole::AdultCreatorVerified,
            'age_group' => AgeGroup::AdultVerified,
            'creator_verified_at' => Carbon::now(),
        ]);

        $this->assertFalse($unverifiedTimestamp->canMonetizeProjects());
        $this->assertTrue($fullyVerified->canMonetizeProjects());
        $this->assertTrue($fullyVerified->canManagePayouts());
        $this->assertTrue($fullyVerified->canShowRevenueAdsOnProjectPages());
    }

    public function test_monetization_denies_mismatched_verified_role_age_group_and_timestamp(): void
    {
        $verifiedRoleButJunior = (new User)->forceFill([
            'role' => UserRole::AdultCreatorVerified,
            'age_group' => AgeGroup::Junior,
            'creator_verified_at' => Carbon::now(),
        ]);

        $verifiedRoleButUnverifiedAdult = (new User)->forceFill([
            'role' => UserRole::AdultCreatorVerified,
            'age_group' => AgeGroup::AdultUnverified,
            'creator_verified_at' => Carbon::now(),
        ]);

        $juniorCreatorWithAdultVerificationData = (new User)->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::AdultVerified,
            'creator_verified_at' => Carbon::now(),
        ]);

        $this->assertFalse($verifiedRoleButJunior->canMonetizeProjects());
        $this->assertFalse($verifiedRoleButUnverifiedAdult->canMonetizeProjects());
        $this->assertFalse($juniorCreatorWithAdultVerificationData->canMonetizeProjects());
    }

    public function test_member_and_advertiser_cannot_publish_projects(): void
    {
        $member = (new User)->forceFill(['role' => UserRole::Member]);
        $advertiser = (new User)->forceFill(['role' => UserRole::Advertiser, 'age_group' => AgeGroup::AdultVerified]);

        $this->assertFalse($member->canPublishProjects());
        $this->assertFalse($advertiser->canPublishProjects());
    }

    public function test_moderation_and_admin_privileges_are_separate_from_creator_privileges(): void
    {
        $moderator = (new User)->forceFill(['role' => UserRole::Moderator, 'age_group' => AgeGroup::AdultVerified]);
        $administrator = (new User)->forceFill(['role' => UserRole::Administrator, 'age_group' => AgeGroup::AdultVerified]);

        $this->assertTrue($moderator->canModerateContent());
        $this->assertFalse($moderator->canAdministerPlatform());
        $this->assertFalse($moderator->canPublishProjects());

        $this->assertTrue($administrator->canModerateContent());
        $this->assertTrue($administrator->canAdministerPlatform());
        $this->assertFalse($administrator->canPublishProjects());
    }

    public function test_sensitive_role_and_verification_fields_are_not_mass_assignable(): void
    {
        $user = new User([
            'name' => 'Example',
            'email' => 'example@safedrop.test',
            'password' => 'secret',
            'role' => UserRole::Administrator,
            'age_group' => AgeGroup::AdultVerified,
            'creator_verified_at' => Carbon::now(),
        ]);

        $this->assertSame(UserRole::Member, $user->role);
        $this->assertSame(AgeGroup::Junior, $user->age_group);
        $this->assertNull($user->creator_verified_at);
    }

    public function test_persisted_role_config_matches_user_role_enum_cases(): void
    {
        $configuredRoles = config('safedrop.roles');
        $enumRoles = array_map(
            fn (UserRole $role): string => $role->value,
            UserRole::cases(),
        );

        sort($configuredRoles);
        sort($enumRoles);

        $this->assertSame($enumRoles, $configuredRoles);
        $this->assertContains('guest', config('safedrop.access_actors'));
        $this->assertNotContains('guest', $configuredRoles);
    }
}
