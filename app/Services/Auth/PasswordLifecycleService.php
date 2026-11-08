<?php

namespace App\Services\Auth;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class PasswordLifecycleService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Generate a cryptographically secure temporary password.
     */
    public function generateTemporaryPassword(): string
    {
        return Str::password(16, letters: true, numbers: true, symbols: true, spaces: false);
    }

    /**
     * Complete first login password change for a user with must_change_password = true.
     *
     * @return array{user: User, pending_choice_id: string}
     */
    public function completeFirstLoginPasswordChange(User $user, string $newPassword): array
    {
        $pendingChoiceId = (string) Str::uuid();

        $result = $this->auditService->executeAtomic(
            operation: function () use ($user, $newPassword, $pendingChoiceId) {
                $version = $user->credential_version;
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                if (! $user->is_active || ! $user->must_change_password || $user->credential_version !== $version
                    || $user->temporary_password_expires_at?->isPast()) {
                    throw new LogicException('Initial password context is no longer valid.');
                }

                $user->password = Hash::make($newPassword);
                $user->must_change_password = false;
                $user->password_changed_at = now();
                $user->temporary_password_expires_at = null;
                $user->credential_version++;
                $user->pending_session_choice_id = $pendingChoiceId;
                $user->pending_session_choice_flow = 'first_setup';
                $user->save();

                return $user;
            },
            auditFactory: fn (User $updatedUser) => [
                'actor_id' => $updatedUser->id,
                'action' => 'INITIAL_PASSWORD_CHANGE',
                'entity_type' => User::class,
                'entity_id' => $updatedUser->id,
                'scope' => AuditScope::USER,
                'changes' => [
                    'must_change_password' => false,
                    'credential_version' => $updatedUser->credential_version,
                ],
                'reason' => 'First-login forced password change completed',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return [
            'user' => $result['result'],
            'pending_choice_id' => $pendingChoiceId,
        ];
    }

    /**
     * Issue an ephemeral proof token after successful OTP validation (D-25).
     */
    public function generateResetProof(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $version = $user->credential_version;
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! $user->is_active || $user->credential_version !== $version) {
                throw new LogicException('Password reset context is no longer valid.');
            }

            $proofToken = Str::random(64);
            $user->password_reset_proof_hash = hash_hmac('sha256', $proofToken, (string) config('app.key'));
            $user->password_reset_proof_expires_at = now()->addMinutes(15);
            $user->password_reset_proof_version = $user->credential_version;
            $user->save();

            return $proofToken;
        });
    }

    /**
     * Complete password reset using valid ephemeral proof token (D-25).
     *
     * @return array{user: User, pending_choice_id: string}
     */
    public function completePasswordReset(User $user, string $proofToken, string $newPassword): array
    {
        $pendingChoiceId = (string) Str::uuid();

        $result = $this->auditService->executeAtomic(
            operation: function () use ($user, $newPassword, $pendingChoiceId, $proofToken) {
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                if (! $user->is_active) {
                    throw new LogicException('Account unavailable.');
                }

                if (empty($user->password_reset_proof_hash) || empty($user->password_reset_proof_expires_at)) {
                    throw new LogicException('No active password reset challenge found for this user.');
                }

                if (now()->gt($user->password_reset_proof_expires_at)) {
                    throw new LogicException('Password reset proof token has expired.');
                }

                if ($user->password_reset_proof_version !== $user->credential_version) {
                    throw new LogicException('Credential version mismatch. Password reset proof is stale.');
                }

                $expectedHash = hash_hmac('sha256', $proofToken, (string) config('app.key'));
                if (! hash_equals($user->password_reset_proof_hash, $expectedHash)) {
                    throw new LogicException('Invalid password reset proof token.');
                }

                $user->password = Hash::make($newPassword);
                $user->must_change_password = false;
                $user->password_changed_at = now();
                $user->temporary_password_expires_at = null;
                $user->credential_version++;

                // Consume proof immediately to prevent replay
                $user->password_reset_proof_hash = null;
                $user->password_reset_proof_expires_at = null;
                $user->password_reset_proof_version = null;

                $user->pending_session_choice_id = $pendingChoiceId;
                $user->pending_session_choice_flow = 'password_reset';
                $user->save();

                return $user;
            },
            auditFactory: fn (User $updatedUser) => [
                'actor_id' => $updatedUser->id,
                'action' => 'RESET_PASSWORD',
                'entity_type' => User::class,
                'entity_id' => $updatedUser->id,
                'scope' => AuditScope::USER,
                'changes' => [
                    'credential_version' => $updatedUser->credential_version,
                ],
                'reason' => 'Self-service password reset with OTP completed',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return [
            'user' => $result['result'],
            'pending_choice_id' => $pendingChoiceId,
        ];
    }

    /**
     * Process mandatory post-password session choice modal (D-25).
     * Choices: 'revoke_others' (Yes) or 'keep_others' (No).
     */
    public function handleSessionChoice(User $user, string $choice, string $choiceId, string $flow, int $credentialVersion, ?string $currentSessionId = null): User
    {
        if (! in_array($choice, ['revoke_others', 'keep_others'], true)) {
            throw new InvalidArgumentException('Invalid session choice. Must be revoke_others or keep_others.');
        }

        $result = $this->auditService->executeAtomic(
            operation: function () use ($user, $choice, $choiceId, $flow, $credentialVersion, $currentSessionId) {
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                if (! $user->is_active || ! $user->pending_session_choice_id
                    || $user->pending_session_choice_id !== $choiceId
                    || $user->pending_session_choice_flow !== $flow
                    || $user->credential_version !== $credentialVersion) {
                    throw new LogicException('Session choice context is no longer valid.');
                }

                if ($choice === 'revoke_others') {
                    $user->session_generation++;

                    // Revoke database sessions if sessions table is used
                    if (DB::getSchemaBuilder()->hasTable('sessions')) {
                        $query = DB::table('sessions')->where('user_id', $user->id);
                        if ($currentSessionId !== null) {
                            $query->where('id', '!=', $currentSessionId);
                        }
                        $query->delete();
                    }
                }

                $user->pending_session_choice_id = null;
                $user->pending_session_choice_flow = null;
                $user->save();

                return $user;
            },
            auditFactory: fn (User $updatedUser) => [
                'actor_id' => $updatedUser->id,
                'action' => 'SESSION_CHOICE',
                'entity_type' => User::class,
                'entity_id' => $updatedUser->id,
                'scope' => AuditScope::USER,
                'changes' => [
                    'choice' => $choice,
                    'session_generation' => $updatedUser->session_generation,
                ],
                'reason' => 'Post-commit session revocation choice processed',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return $result['result'];
    }
}
