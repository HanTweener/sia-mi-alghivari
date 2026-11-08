<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Auth\PasswordLifecycleService;
use Fouladgar\OTP\Facades\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivation_is_checked_against_the_database_on_each_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        User::whereKey($user->id)->update(['is_active' => false]);

        $this->patchJson('/profile', ['name' => 'Unauthorized update'])->assertUnauthorized();
        $this->assertNotSame('Unauthorized update', $user->fresh()->name);
        $this->assertGuest();
    }

    public function test_generation_mismatch_and_missing_generation_reject_sessions(): void
    {
        $user = User::factory()->create(['session_generation' => 2]);
        $this->actingAs($user)->withSession(['session_generation' => 1])
            ->getJson('/profile')->assertUnauthorized();

        $this->actingAs($user)->withSession(['session_generation' => null])
            ->getJson('/profile')->assertUnauthorized();
    }

    public function test_broadcasting_requires_current_active_session_and_completed_password_setup(): void
    {
        $user = User::factory()->create();
        $payload = ['socket_id' => '123.456', 'channel_name' => 'private-users.'.$user->id];

        $this->actingAs($user)->withSession(['session_generation' => 99])
            ->postJson('/broadcasting/auth', $payload)->assertUnauthorized();

        $user->update(['must_change_password' => true]);
        $this->actingAs($user)->postJson('/broadcasting/auth', $payload)->assertForbidden();

        $user->update(['must_change_password' => false, 'is_active' => false]);
        $this->actingAs($user)->postJson('/broadcasting/auth', $payload)->assertUnauthorized();
    }

    public function test_old_authenticated_session_cannot_choose_for_password_reset(): void
    {
        $user = User::factory()->create([
            'pending_session_choice_id' => 'reset-operation',
            'pending_session_choice_flow' => 'password_reset',
        ]);

        $this->actingAs($user)->post('/auth/setup-password/session-choice', ['choice' => 'keep_others'])
            ->assertForbidden();
        $this->assertSame('reset-operation', $user->fresh()->pending_session_choice_id);
    }

    public function test_active_user_can_authorize_only_their_own_broadcast_channel(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'driver' => 'pusher', 'key' => 'test-key', 'secret' => 'test-secret',
                'app_id' => 'test-app', 'options' => ['cluster' => 'mt1'],
            ],
        ]);
        require base_path('routes/channels.php');

        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->postJson('/broadcasting/auth', [
            'socket_id' => '123.456', 'channel_name' => 'private-users.'.$user->id,
        ])->assertOk()->assertJsonStructure(['auth']);

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '123.456', 'channel_name' => 'private-users.'.$other->id,
        ])->assertForbidden();
    }

    public function test_stale_operation_and_duplicate_choice_cannot_change_generation(): void
    {
        $user = User::factory()->create([
            'pending_session_choice_id' => 'current-operation',
            'pending_session_choice_flow' => 'first_setup',
            'credential_version' => 2,
        ]);

        $this->actingAs($user)->withSession([
            'password_choice_id' => 'current-operation',
            'password_choice_flow' => 'first_setup',
            'password_choice_version' => 1,
        ])->post('/auth/setup-password/session-choice', ['choice' => 'revoke_others'])->assertForbidden();

        $service = app(PasswordLifecycleService::class);
        $service->handleSessionChoice($user, 'revoke_others', 'current-operation', 'first_setup', 2);
        try {
            $service->handleSessionChoice($user, 'revoke_others', 'current-operation', 'first_setup', 2);
            $this->fail('A consumed choice was accepted.');
        } catch (LogicException) {
            $this->assertSame(1, $user->fresh()->session_generation);
        }
    }

    public function test_login_initializes_generation_and_binds_latest_password_choice(): void
    {
        $user = User::factory()->create([
            'session_generation' => 3,
            'pending_session_choice_id' => 'latest-operation',
            'pending_session_choice_flow' => 'first_setup',
            'credential_version' => 4,
        ]);

        $this->post('/login', ['username' => $user->username, 'password' => 'password123456'])
            ->assertSessionHas('session_generation', 3)
            ->assertSessionHas('password_choice_id', 'latest-operation')
            ->assertSessionHas('password_choice_version', 4);
        $this->post('/auth/setup-password/session-choice', ['choice' => 'revoke_others'])
            ->assertRedirect('/')->assertSessionHas('session_generation', 4);
        $this->get('/profile')->assertOk();
    }

    public function test_expired_temporary_password_is_rejected_at_login(): void
    {
        $user = User::factory()->temporaryPassword()->create(['temporary_password_expires_at' => now()->subSecond()]);

        $this->post('/login', ['username' => $user->username, 'password' => 'password123456'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_temporary_session_cannot_become_normal_after_another_session_finishes_setup(): void
    {
        $user = User::factory()->temporaryPassword()->create();
        $this->actingAs($user);
        $result = app(PasswordLifecycleService::class)->completeFirstLoginPasswordChange($user->fresh(), 'new-password123456');
        app(PasswordLifecycleService::class)->handleSessionChoice(
            $result['user'], 'keep_others', $result['pending_choice_id'], 'first_setup', $result['user']->credential_version
        );

        $this->getJson('/profile')->assertUnauthorized();
    }

    public function test_reset_proof_cannot_be_replayed_from_a_stale_model_snapshot(): void
    {
        $user = User::factory()->create();
        $service = app(PasswordLifecycleService::class);
        $proof = $service->generateResetProof($user);
        $first = $user->fresh();
        $stale = $user->fresh();
        $service->completePasswordReset($first, $proof, 'first-new-password123456');

        try {
            $service->completePasswordReset($stale, $proof, 'replayed-password123456');
            $this->fail('A consumed reset proof was accepted.');
        } catch (LogicException) {
            $this->assertTrue(Hash::check('first-new-password123456', $user->fresh()->password));
            $this->assertSame(1, DB::table('audit_logs')->where('action', 'RESET_PASSWORD')->count());
        }
    }

    public function test_reset_proof_from_another_browser_is_rejected(): void
    {
        $user = User::factory()->create();
        $proof = app(PasswordLifecycleService::class)->generateResetProof($user);

        $this->post('/reset-password', [
            'token' => $proof, 'email' => $user->email,
            'password' => 'new-password123456', 'password_confirmation' => 'new-password123456',
        ])->assertForbidden();
        $this->assertTrue(Hash::check('password123456', $user->fresh()->password));
    }

    public function test_reset_audit_failure_rolls_back_password_proof_and_pending_operation(): void
    {
        $user = User::factory()->create();
        $proof = app(PasswordLifecycleService::class)->generateResetProof($user);
        $audit = Mockery::mock(AuditService::class)->makePartial();
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('Simulated audit failure'));

        try {
            (new PasswordLifecycleService($audit))->completePasswordReset($user, $proof, 'new-password123456');
            $this->fail('Audit failure was ignored.');
        } catch (RuntimeException) {
            $user->refresh();
            $this->assertTrue(Hash::check('password123456', $user->password));
            $this->assertNotNull($user->password_reset_proof_hash);
            $this->assertNull($user->pending_session_choice_id);
            $this->assertSame(0, $user->credential_version);
        }
    }

    public function test_session_cleanup_and_generation_roll_back_if_audit_fails(): void
    {
        $user = User::factory()->create([
            'pending_session_choice_id' => 'choice-operation',
            'pending_session_choice_flow' => 'first_setup',
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);
        $audit = Mockery::mock(AuditService::class)->makePartial();
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('Simulated audit failure'));

        try {
            (new PasswordLifecycleService($audit))->handleSessionChoice($user, 'revoke_others', 'choice-operation', 'first_setup', 0);
            $this->fail('Audit failure was ignored.');
        } catch (RuntimeException) {
            $this->assertSame(0, $user->fresh()->session_generation);
            $this->assertSame('choice-operation', $user->fresh()->pending_session_choice_id);
            $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        }
    }

    public function test_stale_first_setup_model_cannot_overwrite_new_password(): void
    {
        $user = User::factory()->temporaryPassword()->create();
        $stale = $user->fresh();
        app(PasswordLifecycleService::class)->completeFirstLoginPasswordChange($user, 'new-password123456');

        try {
            app(PasswordLifecycleService::class)->completeFirstLoginPasswordChange($stale, 'stale-password123456');
            $this->fail('Stale temporary credentials were accepted.');
        } catch (LogicException) {
            $this->assertTrue(Hash::check('new-password123456', $user->fresh()->password));
            $this->assertSame(1, DB::table('audit_logs')->where('action', 'INITIAL_PASSWORD_CHANGE')->count());
        }
    }

    public function test_otp_resend_invalidates_previous_reset_proof(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        app(PasswordLifecycleService::class)->generateResetProof($user);

        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $this->assertNull($user->fresh()->password_reset_proof_hash);
    }

    public function test_otp_resend_rejects_old_code_and_existing_browser_proof(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $oldProof = app(PasswordLifecycleService::class)->generateResetProof($user);
        OTP::purpose('password_reset')->fake($user->email, 'old-code');

        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $this->post('/reset-password/verify', ['email' => $user->email, 'otp' => 'old-code'])
            ->assertSessionHasErrors('otp');

        $this->withSession([
            'password_reset_proof' => $oldProof, 'password_reset_proof_email' => $user->email,
        ])->post('/reset-password', [
            'token' => $oldProof, 'email' => $user->email,
            'password' => 'new-password123456', 'password_confirmation' => 'new-password123456',
        ])->assertSessionHasErrors('token');
        $this->assertTrue(Hash::check('password123456', $user->fresh()->password));
    }

    public function test_proof_issuance_failure_rolls_back_otp_consumption(): void
    {
        $user = User::factory()->create();
        OTP::purpose('password_reset')->fake($user->email, '123456');
        $this->mock(PasswordLifecycleService::class)
            ->shouldReceive('generateResetProof')->once()->andThrow(new RuntimeException('Simulated proof storage failure'));
        $this->withoutExceptionHandling();

        try {
            $this->post('/reset-password/verify', ['email' => $user->email, 'otp' => '123456']);
            $this->fail('Proof storage failure was ignored.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('otp_tokens', ['recipient' => $user->email, 'purpose' => 'password_reset']);
            $this->assertNull($user->fresh()->password_reset_proof_hash);
        }
    }

    public function test_login_and_otp_requests_are_rate_limited(): void
    {
        Notification::fake();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withHeader('X-Forwarded-For', '192.0.2.'.($attempt + 1))
                ->post('/login', ['username' => 'unknown', 'password' => 'wrong'])->assertSessionHasErrors();
        }
        $this->withHeader('X-Forwarded-For', '192.0.2.100')
            ->post('/login', ['username' => 'unknown', 'password' => 'wrong'])->assertStatus(429);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertRedirect();
        }
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertStatus(429);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/reset-password/verify', ['email' => 'unknown@example.test', 'otp' => '123456'])->assertSessionHasErrors('otp');
        }
        $this->post('/reset-password/verify', ['email' => 'unknown@example.test', 'otp' => '123456'])->assertStatus(429);
    }

    public function test_user_serialization_hides_password_proof_and_operation_identifiers(): void
    {
        $user = User::factory()->create([
            'password_reset_proof_hash' => 'sensitive-hash',
            'pending_session_choice_id' => 'sensitive-operation',
        ]);

        $this->assertArrayNotHasKey('password_reset_proof_hash', $user->toArray());
        $this->assertArrayNotHasKey('password_reset_proof_expires_at', $user->toArray());
        $this->assertArrayNotHasKey('password_reset_proof_version', $user->toArray());
        $this->assertArrayNotHasKey('pending_session_choice_id', $user->toArray());
    }
}
