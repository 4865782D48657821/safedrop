<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_registration_uses_conservative_defaults_and_ignores_privilege_input(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $response = $this->post('/register', [
            'name' => 'New Member',
            'email' => 'new-member@safedrop.test',
            'password' => 'safe-password',
            'password_confirmation' => 'safe-password',
            'role' => UserRole::Administrator->value,
            'age_group' => AgeGroup::AdultVerified->value,
            'creator_verified_at' => now()->toISOString(),
        ]);

        $response->assertRedirect(route('account.show'));

        $user = User::query()->where('email', 'new-member@safedrop.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Member, $user->role);
        $this->assertSame(AgeGroup::Junior, $user->age_group);
        $this->assertNull($user->creator_verified_at);
        $this->assertFalse($user->canPublishProjects());
        $this->assertFalse($user->canMonetizeProjects());
    }

    public function test_user_can_login_and_logout(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = User::query()->create([
            'name' => 'Existing Member',
            'email' => 'existing@safedrop.test',
            'password' => 'safe-password',
        ]);

        $login = $this->post('/login', [
            'email' => 'existing@safedrop.test',
            'password' => 'safe-password',
        ]);

        $login->assertRedirect(route('account.show'));
        $this->assertAuthenticatedAs($user);

        $logout = $this->post('/logout');

        $logout->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_invalid_login_keeps_user_guest(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        User::query()->create([
            'name' => 'Existing Member',
            'email' => 'existing@safedrop.test',
            'password' => 'safe-password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'existing@safedrop.test',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_creator_dashboard_requires_authentication_and_creator_role(): void
    {
        $this->get('/creator')->assertRedirect(route('login'));

        $member = User::query()->create([
            'name' => 'Member',
            'email' => 'member@safedrop.test',
            'password' => 'safe-password',
        ]);

        $this->actingAs($member)->get('/creator')->assertForbidden();

        $creator = User::query()->create([
            'name' => 'Junior Creator',
            'email' => 'creator@safedrop.test',
            'password' => 'safe-password',
        ]);
        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        $this->actingAs($creator)
            ->get('/creator')
            ->assertOk()
            ->assertSee('Creator Tools')
            ->assertSee('Revenue ads are disabled');
    }
}
