<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Models\User;
use App\Services\Audit\AuditService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditService;
    }

    public function test_audit_log_is_recorded_when_domain_operation_executes(): void
    {
        $actor = User::create([
            'name' => 'Master User',
            'username' => 'master',
            'email' => 'master@example.com',
            'password' => 'secret123456789',
        ]);

        $result = $this->auditService->executeAtomic(
            operation: function () {
                return User::create([
                    'name' => 'John Doe',
                    'username' => 'johndoe',
                    'email' => 'john@example.com',
                    'password' => 'secret123456789',
                ]);
            },
            auditFactory: function (User $newUser) use ($actor) {
                return [
                    'actor_id' => $actor->id,
                    'action' => 'CREATE',
                    'entity_type' => User::class,
                    'entity_id' => $newUser->id,
                    'scope' => AuditScope::USER,
                    'changes' => [
                        'username' => $newUser->username,
                        'email' => $newUser->email,
                    ],
                    'reason' => 'Administrative user onboarding',
                    'visibility' => AuditVisibility::INTERNAL,
                ];
            }
        );

        $this->assertDatabaseHas('users', ['username' => 'johndoe']);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $actor->id,
            'action' => 'CREATE',
            'entity_id' => (string) $result['result']->id,
        ]);
    }

    public function test_atomic_rollback_when_domain_or_audit_fails(): void
    {
        $actor = User::create([
            'name' => 'Master User',
            'username' => 'master_fail',
            'email' => 'master_fail@example.com',
            'password' => 'secret123456789',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Simulated failure during atomic transaction');

        $this->auditService->executeAtomic(
            operation: function () {
                User::create([
                    'name' => 'Failed User',
                    'username' => 'failed_user',
                    'email' => 'failed@example.com',
                    'password' => 'secret123456789',
                ]);

                throw new Exception('Simulated failure during atomic transaction');
            },
            auditFactory: function () use ($actor) {
                return [
                    'actor_id' => $actor->id,
                    'action' => 'CREATE',
                    'entity_type' => User::class,
                    'entity_id' => 999,
                    'scope' => AuditScope::USER,
                    'changes' => [],
                ];
            }
        );

        $this->assertDatabaseMissing('users', ['username' => 'failed_user']);
        $this->assertDatabaseMissing('audit_logs', ['entity_id' => '999']);
    }

    public function test_audit_log_redacts_sensitive_credentials(): void
    {
        $audit = $this->auditService->record(
            actorUserId: null,
            action: 'UPDATE_PASSWORD',
            entityType: User::class,
            entityId: 1,
            scope: AuditScope::USER,
            allowedChanges: [
                'password' => 'super_secret_plain_or_hash',
                'remember_token' => 'random_token',
                'name' => 'Updated Name',
            ]
        );

        $this->assertEquals('[REDACTED]', $audit->allowed_changes['password']);
        $this->assertEquals('[REDACTED]', $audit->allowed_changes['remember_token']);
        $this->assertEquals('Updated Name', $audit->allowed_changes['name']);
    }

    public function test_audit_log_is_append_only_and_cannot_be_updated(): void
    {
        $audit = $this->auditService->record(
            actorUserId: null,
            action: 'CREATE',
            entityType: 'System',
            entityId: 1,
            scope: AuditScope::SYSTEM,
            allowedChanges: ['status' => 'init']
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs are append-only and cannot be modified.');

        $audit->update(['reason' => 'Tampered reason']);
    }

    public function test_audit_log_is_append_only_and_cannot_be_deleted(): void
    {
        $audit = $this->auditService->record(
            actorUserId: null,
            action: 'CREATE',
            entityType: 'System',
            entityId: 1,
            scope: AuditScope::SYSTEM,
            allowedChanges: ['status' => 'init']
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs are append-only and cannot be deleted.');

        $audit->delete();
    }
}
