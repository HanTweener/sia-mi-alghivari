<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('demoAccounts', 7)
            ->where('demoAccounts.4.username', 'guru.ridwan')
            ->where('demoAccounts.4.role', 'Guru Mengajar (Mapel)')
        );
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'username' => 'test.admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole(Role::MASTER_ADMIN);

        $response = $this->post('/login', [
            'username' => 'test.admin',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'username' => 'test.admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'test.admin',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'username' => 'inactive.user',
            'email' => 'inactive@test.com',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'inactive.user',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_users_can_logout(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'username' => 'test.user',
            'email' => 'user@test.com',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/login');
    }
}
