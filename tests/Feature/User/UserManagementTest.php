<?php

namespace Tests\Feature\User;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_admin_can_view_user_list(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        $response = $this->actingAs($master)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Users/Index'));
    }

    public function test_unauthorized_user_cannot_view_user_list(): void
    {
        $guardian = User::factory()->create();
        $guardian->assignRole(Role::GUARDIAN);

        $response = $this->actingAs($guardian)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_master_admin_can_create_user_with_multi_roles_and_temporary_password(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        $response = $this->actingAs($master)->post('/admin/users', [
            'name' => 'Ustadz Ahmad',
            'username' => 'ahmad_guru',
            'email' => 'ahmad@sekolah.sch.id',
            'phone' => '08123456789',
            'address' => 'Jl. Cisaranten Kulon No. 10',
            'roles' => [Role::TEACHER->value, Role::GUARDIAN->value],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('temporary_credentials');

        $this->assertDatabaseHas('users', [
            'username' => 'ahmad_guru',
            'email' => 'ahmad@sekolah.sch.id',
            'must_change_password' => true,
            'email_verification_source' => 'master_attested',
        ]);

        $createdUser = User::where('username', 'ahmad_guru')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasRole(Role::TEACHER));
        $this->assertTrue($createdUser->hasRole(Role::GUARDIAN));

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $master->id,
            'action' => 'CREATE_USER',
            'entity_id' => (string) $createdUser->id,
        ]);
    }

    public function test_duplicate_username_or_email_is_rejected(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        User::factory()->create([
            'username' => 'existing_user',
            'email' => 'existing@example.com',
        ]);

        $response = $this->actingAs($master)->post('/admin/users', [
            'name' => 'Another User',
            'username' => 'existing_user',
            'email' => 'existing@example.com',
            'roles' => [Role::TEACHER->value],
        ]);

        $response->assertSessionHasErrors(['username', 'email']);
    }

    public function test_master_admin_can_deactivate_user(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        $target = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($master)->delete("/admin/users/{$target->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $master->id,
            'action' => 'DEACTIVATE_USER',
            'entity_id' => (string) $target->id,
        ]);
    }
}
