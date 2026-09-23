<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

/**
 * Writes the message to the application log so verification codes can be read
 * during development without contacting a provider.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::channel(config('logging.default'))->info('SMS dispatched', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
