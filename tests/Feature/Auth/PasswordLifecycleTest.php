<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_must_change_password_is_redirected_to_setup(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);
        $user->assignRole(Role::TEACHER);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect('/auth/setup-password');
    }

    public function test_user_with_pending_session_choice_is_redirected_to_setup(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'pending_session_choice_id' => 'temp-uuid-1234',
        ]);
        $user->assignRole(Role::TEACHER);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect('/auth/setup-password');
    }

    public function test_normal_user_is_redirected_away_from_setup(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'pending_session_choice_id' => null,
        ]);
        $user->assignRole(Role::TEACHER);

        $response = $this->actingAs($user)->get('/auth/setup-password');

        $response->assertRedirect('/');
    }

    public function test_user_can_submit_first_login_password_change(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'credential_version' => 1,
            'password' => Hash::make('temporary-password'),
        ]);

        $response = $this->actingAs($user)->post('/auth/setup-password', [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect('/auth/setup-password');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertEquals(2, $user->credential_version);
        $this->assertNotNull($user->pending_session_choice_id);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'INITIAL_PASSWORD_CHANGE',
            'entity_id' => (string) $user->id,
        ]);
    }

    public function test_user_can_submit_session_choice_revoke_others(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'pending_session_choice_id' => 'choice-uuid-5678',
            'pending_session_choice_flow' => 'first_setup',
            'session_generation' => 1,
        ]);

        $response = $this->actingAs($user)->withSession([
            'password_choice_id' => 'choice-uuid-5678',
            'password_choice_flow' => 'first_setup',
            'password_choice_version' => $user->credential_version,
        ])->post('/auth/setup-password/session-choice', [
            'choice' => 'revoke_others',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->pending_session_choice_id);
        $this->assertEquals(2, $user->session_generation);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'SESSION_CHOICE',
            'entity_id' => (string) $user->id,
        ]);
    }

    public function test_user_can_submit_session_choice_keep_others(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'pending_session_choice_id' => 'choice-uuid-5678',
            'pending_session_choice_flow' => 'first_setup',
            'session_generation' => 1,
        ]);

        $response = $this->actingAs($user)->withSession([
            'password_choice_id' => 'choice-uuid-5678',
            'password_choice_flow' => 'first_setup',
            'password_choice_version' => $user->credential_version,
        ])->post('/auth/setup-password/session-choice', [
            'choice' => 'keep_others',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->pending_session_choice_id);
        $this->assertEquals(1, $user->session_generation);
    }
}
