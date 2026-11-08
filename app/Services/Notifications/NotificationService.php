<?php

namespace App\Services\Notifications;

use App\Events\InboxChanged;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create an inbox notification for a recipient.
     */
    public function createInboxNotification(
        User|int $recipient,
        string $eventCategory,
        string $summary,
        ?AuditLog $sourceAudit = null,
        ?string $scopeType = null,
        string|int|null $scopeId = null,
        int $visibleItemCount = 1,
        ?string $batchId = null
    ): Notification {
        $recipientId = $recipient instanceof User ? $recipient->id : $recipient;

        $notification = Notification::create([
            'recipient_id' => $recipientId,
            'source_audit_id' => $sourceAudit?->id,
            'batch_id' => $batchId ?? $sourceAudit?->batch_id,
            'event_category' => $eventCategory,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId !== null ? (string) $scopeId : null,
            'sanitized_summary' => $summary,
            'visible_item_count' => $visibleItemCount,
            'read_at' => null,
        ]);

        // Broadcast after transaction commit (F-11, NAR-01, NAR-04)
        DB::afterCommit(function () use ($notification) {
            try {
                broadcast(new InboxChanged($notification));
            } catch (\Throwable $e) {
                Log::warning('Broadcast of InboxChanged skipped or failed', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return $notification;
    }
}
