<?php

namespace App\Console\Commands;

use App\Enums\LicenseStatus;
use App\Models\AlertLog;
use App\Models\License;
use App\Notifications\LicenseExpiringSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendExpiryReminderCommand extends Command
{
    protected $signature = 'license:send-expiry-reminder
        {--dry-run : Tampilkan license yang akan dikirim reminder tanpa eksekusi}';

    protected $description = 'Kirim reminder H-7, H-3, H-1 sebelum license expired via WhatsApp/Email';

    private const REMINDER_DAYS = [7, 3, 1];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $totalSent = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        foreach (self::REMINDER_DAYS as $daysBefore) {
            $targetDate = now()->addDays($daysBefore)->toDateString();

            $licenses = License::query()
                ->with('client')
                ->where('status', LicenseStatus::Active)
                ->whereDate('expires_at', $targetDate)
                ->get();

            if ($licenses->isEmpty()) {
                continue;
            }

            $this->info("H-{$daysBefore}: {$licenses->count()} license jatuh tempo pada {$targetDate}");

            foreach ($licenses as $license) {
                if ($isDryRun) {
                    $this->line("  - [DRY RUN] {$license->client->name} ({$license->license_key})");

                    continue;
                }

                $this->sendReminder($license, $daysBefore, $totalSent, $totalSkipped, $totalFailed);
            }
        }

        if ($isDryRun) {
            $this->comment('Dry run — tidak ada notifikasi dikirim.');

            return self::SUCCESS;
        }

        $this->info("Selesai. Terkirim: {$totalSent}, Skip (duplikat): {$totalSkipped}, Gagal: {$totalFailed}.");

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function sendReminder(
        License $license,
        int $daysBefore,
        int &$sentCounter,
        int &$skippedCounter,
        int &$failedCounter,
    ): void {
        $channels = ['email'];

        if (! empty($license->client->phone_number)) {
            $channels[] = 'whatsapp';
        }

        foreach ($channels as $channel) {
            $alreadySent = AlertLog::query()
                ->where('license_id', $license->id)
                ->where('channel', $channel)
                ->where('milestone_days', $daysBefore)
                ->where('status', 'sent')
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($alreadySent) {
                $skippedCounter++;

                continue;
            }

            $alertLog = AlertLog::create([
                'license_id' => $license->id,
                'channel' => $channel,
                'type' => 'expiry_reminder',
                'milestone_days' => $daysBefore,
                'status' => 'pending',
            ]);

            try {
                $license->client->notify(new LicenseExpiringSoon($license, $daysBefore));

                $alertLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $sentCounter++;
            } catch (\Throwable $e) {
                $alertLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $failedCounter++;

                Log::error('Gagal kirim expiry reminder', [
                    'license_id' => $license->id,
                    'channel' => $channel,
                    'days_before' => $daysBefore,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
