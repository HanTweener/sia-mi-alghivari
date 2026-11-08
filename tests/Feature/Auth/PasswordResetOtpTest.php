<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\PasswordLifecycleService;
use Fouladgar\OTP\Facades\OTP;
use Fouladgar\OTP\Notifications\OTPNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_forgot_password_form(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_request_otp_sends_email_and_stores_hashed_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'is_active' => true,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.verify.show'));

        // Notification is sent to anonymous notifiable routing mail to user's email
        Notification::assertSentTo(
            new AnonymousNotifiable,
            OTPNotification::class,
            function ($notification, $channels, $notifiable) use ($user) {
                return $notifiable->routes['mail'] === $user->email;
            }
        );

        // Verify token is saved in otp_tokens and is hashed with HMAC-SHA256 (64 hex characters)
        $this->assertDatabaseHas('otp_tokens', [
            'recipient' => $user->email,
            'purpose' => 'password_reset',
        ]);

        $record = DB::table('otp_tokens')
            ->where('recipient', $user->email)
            ->where('purpose', 'password_reset')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(64, strlen($record->token)); // 64-char HMAC-SHA256 hash
    }

    public function test_request_otp_for_nonexistent_email_does_not_leak_enumeration(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'tidak.ada@alghivari.sch.id',
        ]);

        $response->assertRedirect(route('password.verify.show'));
        Notification::assertNothingSent();
    }

    public function test_request_otp_for_inactive_user_does_not_send_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'nonaktif@alghivari.sch.id',
            'is_active' => false,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.verify.show'));
        Notification::assertNothingSent();
    }

    public function test_verify_otp_rejects_invalid_or_expired_code(): void
    {
        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'is_active' => true,
        ]);

        // Create token in repository
        OTP::purpose('password_reset')->fake($user->email, '123456');

        $response = $this->post(route('password.verify.submit'), [
            'email' => $user->email,
            'otp' => '999999', // wrong OTP
        ]);

        $response->assertSessionHasErrors(['otp']);
        $this->assertNull($user->fresh()->password_reset_proof_hash);
    }

    public function test_verify_otp_with_valid_code_issues_ephemeral_proof_token(): void
    {
        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'is_active' => true,
        ]);

        OTP::purpose('password_reset')->fake($user->email, '654321');

        $response = $this->post(route('password.verify.submit'), [
            'email' => $user->email,
            'otp' => '654321',
        ]);

        $response->assertRedirect();
        $user = $user->fresh();

        $this->assertNotNull($user->password_reset_proof_hash);
        $this->assertSame(64, strlen($user->password_reset_proof_hash));
        $this->assertNotNull($user->password_reset_proof_expires_at);

        // OTP in otp_tokens must be consumed/revoked
        $this->assertDatabaseMissing('otp_tokens', [
            'recipient' => $user->email,
            'purpose' => 'password_reset',
        ]);
    }

    public function test_complete_password_reset_updates_password_and_clears_proof_token(): void
    {
        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        // Issue proof token
        $proofToken = app(PasswordLifecycleService::class)->generateResetProof($user);

        $response = $this->withSession([
            'password_reset_proof' => $proofToken,
            'password_reset_proof_email' => $user->email,
        ])->post(route('password.reset.update'), [
            'token' => $proofToken,
            'email' => $user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('password.reset.session-choice.show'));

        $user = $user->fresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
        $this->assertNull($user->password_reset_proof_hash);
        $this->assertNull($user->password_reset_proof_expires_at);
        $this->assertNotNull($user->pending_session_choice_id);
        $this->assertSame('password_reset', $user->pending_session_choice_flow);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'RESET_PASSWORD',
            'entity_type' => User::class,
            'entity_id' => $user->id,
        ]);
    }

    public function test_complete_password_reset_rejects_stale_or_invalid_proof_token(): void
    {
        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        app(PasswordLifecycleService::class)->generateResetProof($user);
        $proofToken = 'invalid-proof-token-string';

        $response = $this->withSession([
            'password_reset_proof' => $proofToken,
            'password_reset_proof_email' => $user->email,
        ])->post(route('password.reset.update'), [
            'token' => 'invalid-proof-token-string',
            'email' => $user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertSessionHasErrors(['token']);
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }

    public function test_session_choice_terminates_all_sessions_when_revoke_others_selected(): void
    {
        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'session_generation' => 0,
        ]);

        $choiceId = 'test-choice-uuid-1234';
        $user->pending_session_choice_id = $choiceId;
        $user->pending_session_choice_flow = 'password_reset';
        $user->save();

        // Create mock sessions in database
        DB::table('sessions')->insert([
            ['id' => 'session-1', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Chrome', 'payload' => 'mock', 'last_activity' => time()],
            ['id' => 'session-2', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Firefox', 'payload' => 'mock', 'last_activity' => time()],
        ]);

        $response = $this->withSession([
            'pending_reset_user_id' => $user->id,
            'pending_choice_id' => $choiceId,
            'pending_choice_version' => $user->credential_version,
        ])->post(route('password.reset.session-choice.store'), [
            'choice' => 'revoke_others',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user = $user->fresh();
        $this->assertSame(1, $user->session_generation);
        $this->assertNull($user->pending_session_choice_id);

        // All database sessions for user must be deleted
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_session_choice_retains_sessions_when_keep_others_selected(): void
    {
        $user = User::factory()->create([
            'email' => 'guru.ahmad@alghivari.sch.id',
            'session_generation' => 0,
        ]);

        $choiceId = 'test-choice-uuid-5678';
        $user->pending_session_choice_id = $choiceId;
        $user->pending_session_choice_flow = 'password_reset';
        $user->save();

        DB::table('sessions')->insert([
            ['id' => 'session-1', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Chrome', 'payload' => 'mock', 'last_activity' => time()],
        ]);

        $response = $this->withSession([
            'pending_reset_user_id' => $user->id,
            'pending_choice_id' => $choiceId,
            'pending_choice_version' => $user->credential_version,
        ])->post(route('password.reset.session-choice.store'), [
            'choice' => 'keep_others',
        ]);

        $response->assertRedirect(route('login'));

        $user = $user->fresh();
        $this->assertSame(0, $user->session_generation);
        $this->assertNull($user->pending_session_choice_id);
        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());
    }
}
