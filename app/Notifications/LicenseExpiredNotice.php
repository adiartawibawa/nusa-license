<?php

namespace App\Notifications;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiredNotice extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly License $license,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // WhatsApp channel menyusul saat WhatsAppNotifierService selesai diimplementasi
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('License Anda Telah Kadaluarsa')
            ->greeting("Halo {$notifiable->name},")
            ->line("License dengan key {$this->license->license_key} telah melewati masa berlaku pada {$this->license->expires_at->toDateString()}.")
            ->line('Aplikasi villa Anda saat ini terkunci. Segera lakukan perpanjangan untuk mengaktifkan kembali layanan.')
            ->action('Hubungi Admin', url('/'))
            ->line('Terima kasih.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
