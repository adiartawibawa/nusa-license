<?php

namespace App\Services\Notification;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        $phoneNumber = $notifiable->phone_number;

        if (empty($phoneNumber)) {
            return;
        }

        // Stub — sesuaikan dengan provider WA Business API yang dipakai (Twilio, Fonnte, Wablas, dll)
        $response = Http::withToken(config('services.whatsapp.token'))
            ->post(config('services.whatsapp.endpoint'), [
                'to' => $phoneNumber,
                'message' => $message,
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp notification gagal terkirim', [
                'phone' => $phoneNumber,
                'response' => $response->body(),
            ]);

            throw new \RuntimeException('WhatsApp API request failed: '.$response->status());
        }
    }
}
