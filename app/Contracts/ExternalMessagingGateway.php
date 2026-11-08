<?php

namespace App\Contracts;

interface ExternalMessagingGateway
{
    /**
     * Send an external notification message (SMS / WhatsApp).
     *
     * @param  string  $recipientPhone  Phone number (E.164 or national format)
     * @param  string  $message  The notification message text
     * @param  array<string, mixed>  $context  Additional metadata (channel, report_id, etc.)
     * @return bool True if message was successfully dispatched
     */
    public function send(string $recipientPhone, string $message, array $context = []): bool;
}
