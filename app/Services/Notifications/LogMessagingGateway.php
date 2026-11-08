<?php

namespace App\Services\Notifications;

use App\Contracts\ExternalMessagingGateway;
use Illuminate\Support\Facades\Log;

class LogMessagingGateway implements ExternalMessagingGateway
{
    /**
     * Send an external notification message by logging it.
     * Serves as default zero-cost driver for local and self-hosted deployments.
     *
     * @param  array<string, mixed>  $context
     */
    public function send(string $recipientPhone, string $message, array $context = []): bool
    {
        $channel = $context['channel'] ?? 'whatsapp';

        Log::info("External [{$channel}] dispatched to {$recipientPhone}: {$message}", [
            'recipient_phone' => $recipientPhone,
            'channel' => $channel,
            'context' => $context,
        ]);

        return true;
    }
}
