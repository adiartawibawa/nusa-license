<?php

namespace App\Notifications;

use App\Models\License;
use App\Services\Notification\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringSoon extends Notification
{
    use Queueable;

    public function __construct(
        private readonly License $license,
        private readonly int $daysBefore,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (! empty($notifiable->phone_number)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pengingat: License Anda Akan Berakhir dalam {$this->daysBefore} Hari")
            ->greeting("Halo {$notifiable->name},")
            ->line("License dengan key {$this->license->license_key} akan berakhir pada {$this->license->expires_at->toDateString()}.")
            ->line('Segera lakukan perpanjangan untuk menghindari penguncian layanan otomatis.')
            ->action('Hubungi Admin', url('/'))
            ->line('Terima kasih.');
    }

    public function toWhatsApp(object $notifiable): string
    {
        return "Halo {$notifiable->name}, license Anda ({$this->license->license_key}) akan berakhir dalam {$this->daysBefore} hari pada {$this->license->expires_at->toDateString()}. Segera perpanjang untuk menghindari penguncian otomatis.";
    }
}
