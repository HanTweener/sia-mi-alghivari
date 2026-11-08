<?php

namespace Tests\Feature\Profile;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Profile/Edit'));
    }

    public function test_user_can_update_allowed_profile_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'phone' => '0811111111',
            'address' => 'Old Address',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'phone' => '0822222222',
            'address' => 'New Valid Address',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '0822222222',
            'address' => 'New Valid Address',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'UPDATE_PROFILE',
            'entity_id' => (string) $user->id,
        ]);
    }

    public function test_user_cannot_escalate_roles_or_credentials_via_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::GUARDIAN);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Attempt Escalation',
            'roles' => [Role::MASTER_ADMIN->value],
            'is_active' => true,
            'must_change_password' => true,
            'password' => 'hackedpassword',
        ]);

        $response->assertSessionHasNoErrors();
        $user->refresh();

        $this->assertEquals('Attempt Escalation', $user->name);
        $this->assertFalse($user->hasRole(Role::MASTER_ADMIN));
        $this->assertTrue($user->hasRole(Role::GUARDIAN));
    }
}
