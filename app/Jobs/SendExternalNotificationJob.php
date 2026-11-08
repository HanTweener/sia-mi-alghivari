<?php

namespace App\Jobs;

use App\Contracts\ExternalMessagingGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendExternalNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $recipientPhone,
        public string $message,
        public array $context = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExternalMessagingGateway $gateway): void
    {
        try {
            $gateway->send($this->recipientPhone, $this->message, $this->context);
        } catch (\Throwable $e) {
            Log::error('External messaging gateway error: '.$e->getMessage(), [
                'recipient' => $this->recipientPhone,
                'context' => $this->context,
                'exception' => $e,
            ]);

            // Re-throw if within retry limit so queue worker can retry
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
