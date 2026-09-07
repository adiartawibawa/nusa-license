<?php

namespace App\Console\Commands;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Notifications\LicenseExpiredNotice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('license:auto-expire
        {--dry-run : Tampilkan license yang akan diubah tanpa eksekusi}')]
#[Description('Set status EXPIRED untuk semua license aktif yang sudah lewat expires_at (dan grace period jika ada)')]
class AutoSuspendExpiredLicenseCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Hanya proses status ACTIVE — SUSPENDED biarkan tetap suspended (manual override menang),
        // dan yang sudah EXPIRED tidak perlu diproses ulang.
        $query = License::query()
            ->with('client')
            ->where('status', LicenseStatus::Active)
            ->where(function ($q) {
                $q->whereNull('grace_period_until')
                    ->where('expires_at', '<', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('grace_period_until')
                            ->where('grace_period_until', '<', now());
                    });
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('Tidak ada license yang perlu di-expire.');

            return self::SUCCESS;
        }

        $this->info("Ditemukan {$total} license untuk di-expire.");

        if ($isDryRun) {
            $this->table(
                ['License Key', 'Client', 'Expires At', 'Grace Until'],
                $query->get()->map(fn (License $l) => [
                    $l->license_key,
                    $l->client->name,
                    $l->expires_at->toDateString(),
                    $l->grace_period_until?->toDateString() ?? '-',
                ])
            );

            $this->comment('Dry run — tidak ada perubahan disimpan.');

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        // Chunk supaya tidak load semua row ke memori sekaligus kalau data sudah besar
        $query->chunkById(100, function ($licenses) use (&$processed, &$failed) {
            foreach ($licenses as $license) {
                try {
                    DB::transaction(function () use ($license) {
                        $license->update([
                            'status' => LicenseStatus::Expired,
                            'last_status_changed_at' => now(),
                        ]);

                        Cache::forget("license_status:{$license->license_key}");
                    });

                    $license->client->notify(new LicenseExpiredNotice($license));

                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;

                    Log::error('Gagal auto-expire license', [
                        'license_id' => $license->id,
                        'license_key' => $license->license_key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->forgetWidgetCaches();

        $this->info("Selesai. Berhasil: {$processed}, Gagal: {$failed}.");

        Log::info('AutoSuspendExpiredLicenseCommand selesai', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function forgetWidgetCaches(): void
    {
        Cache::forget('widget:license_status_counts');
        Cache::forget('widget:license_expiring_soon');

        foreach (LicenseStatus::cases() as $status) {
            Cache::forget("widget:license_trend:{$status->value}");
        }
    }
}
