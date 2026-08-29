<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * BusyBee-shaped SMS adapter. Portfolio stub -- does not call a live gateway.
 * Swap send() body for a real HTTP client when credentials are available.
 */
class BusyBeeSmsGateway implements SmsGateway
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $sender,
    ) {
    }

    public function send(string $to, string $message): bool
    {
        Log::info('stocklane.sms.busybee_stub', [
            'to' => $to,
            'sender' => $this->sender,
            'api_key_present' => $this->apiKey !== '',
            'message' => $message,
        ]);

        return true;
    }
}
