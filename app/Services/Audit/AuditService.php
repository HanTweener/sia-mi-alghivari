<?php

namespace App\Services\Audit;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuditService
{
    /**
     * Disallowed / sensitive field keys that must never be recorded in audit diffs.
     */
    protected const SENSITIVE_FIELDS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token_hash',
    ];

    /**
     * Record an audit log entry.
     *
     * @param  array<string, mixed>  $allowedChanges
     */
    public function record(
        ?int $actorUserId,
        string $action,
        string $entityType,
        string|int $entityId,
        AuditScope $scope,
        array $allowedChanges,
        ?string $reason = null,
        ?string $batchId = null,
        AuditVisibility $visibility = AuditVisibility::INTERNAL
    ): AuditLog {
        $sanitizedChanges = $this->sanitizeChanges($allowedChanges);

        return AuditLog::create([
            'actor_user_id' => $actorUserId,
            'action' => strtoupper($action),
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'scope' => $scope,
            'event_visibility' => $visibility,
            'allowed_changes' => $sanitizedChanges,
            'reason' => $reason,
            'batch_id' => $batchId,
            'created_at' => now(),
        ]);
    }

    /**
     * Execute a domain operation atomically with audit logging and optional notification creation.
     * Guaranteed rollback if either the domain operation or audit fails.
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @param callable(T): array{
     *     actor_id: ?int,
     *     action: string,
     *     entity_type: string,
     *     entity_id: string|int,
     *     scope: AuditScope,
     *     changes: array<string, mixed>,
     *     reason?: ?string,
     *     batch_id?: ?string,
     *     visibility?: AuditVisibility
     * } $auditFactory
     * @param  (callable(T, AuditLog): void)|null  $notificationFactory
     * @return array{result: T, audit: AuditLog}
     *
     * @throws Throwable
     */
    public function executeAtomic(
        callable $operation,
        callable $auditFactory,
        ?callable $notificationFactory = null
    ): array {
        return DB::transaction(function () use ($operation, $auditFactory, $notificationFactory) {
            $result = $operation();

            $auditData = $auditFactory($result);

            $audit = $this->record(
                actorUserId: $auditData['actor_id'] ?? null,
                action: $auditData['action'],
                entityType: $auditData['entity_type'],
                entityId: $auditData['entity_id'],
                scope: $auditData['scope'],
                allowedChanges: $auditData['changes'] ?? [],
                reason: $auditData['reason'] ?? null,
                batchId: $auditData['batch_id'] ?? null,
                visibility: $auditData['visibility'] ?? AuditVisibility::INTERNAL
            );

            if ($notificationFactory !== null) {
                $notificationFactory($result, $audit);
            }

            return [
                'result' => $result,
                'audit' => $audit,
            ];
        });
    }

    /**
     * Sanitize diff attributes to redact credentials and tokens.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function sanitizeChanges(array $changes): array
    {
        $sanitized = [];

        foreach ($changes as $key => $value) {
            if (in_array(strtolower($key), self::SENSITIVE_FIELDS, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeChanges($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
