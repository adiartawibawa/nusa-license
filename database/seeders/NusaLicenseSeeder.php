<?php

namespace Database\Seeders;

use App\Enums\TelemetryResult;
use App\Models\AlertLog;
use App\Models\Client;
use App\Models\Domain;
use App\Models\License;
use App\Models\TelemetryLog;
use Illuminate\Database\Seeder;

class NusaLicenseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Client dengan license ACTIVE + domain valid — kasus normal
        Client::factory()
            ->count(5)
            ->has(Domain::factory()->count(1))
            ->has(License::factory())
            ->create()
            ->each(fn (Client $client) => $this->seedTelemetry($client, TelemetryResult::Success));

        // 2. Client dengan license SUSPENDED (kill-switch aktif) — kasus tunggakan
        Client::factory()
            ->count(2)
            ->has(Domain::factory())
            ->has(License::factory()->suspended())
            ->create()
            ->each(function (Client $client) {
                $this->seedTelemetry($client, TelemetryResult::RejectedStatus);
                $this->seedAlertLog($client, 'suspendedNotice');
            });

        // 3. Client dengan license EXPIRED — belum sempat renew
        Client::factory()
            ->count(2)
            ->has(Domain::factory())
            ->has(License::factory()->expired())
            ->create()
            ->each(function (Client $client) {
                $this->seedAlertLog($client, 'expiredNotice');
                // Sudah pernah dapat reminder H-7/H-3/H-1 sebelum akhirnya expired
                $this->seedReminderHistory($client);
            });

        // 4. Client mendekati expiry (untuk test cron reminder)
        Client::factory()
            ->count(3)
            ->has(Domain::factory())
            ->has(License::factory()->expiringSoon())
            ->create()
            ->each(function (Client $client) {
                // Baru dapat reminder H-7, belum H-3/H-1 (karena masih beberapa hari lagi)
                $this->seedAlertLog($client, null, ['milestone_days' => 7]);
            });

        // 5. Kasus UNREGISTERED_DOMAIN — license valid tapi domain belum diverifikasi
        Client::factory()
            ->count(1)
            ->has(Domain::factory()->unverified())
            ->has(License::factory())
            ->create()
            ->each(fn (Client $client) => $this->seedTelemetry($client, TelemetryResult::RejectedDomain));

        // 6. Client non-aktif (soft-deleted candidate, tidak ada license)
        Client::factory()
            ->count(2)
            ->inactive()
            ->create();

        // 7. Beberapa contoh alert yang gagal terkirim (untuk test monitoring/failure handling)
        Client::factory()
            ->count(1)
            ->has(Domain::factory())
            ->has(License::factory()->expiringSoon())
            ->create()
            ->each(fn (Client $client) => $this->seedAlertLog($client, 'failed', ['milestone_days' => 3]));
    }

    private function seedTelemetry(Client $client, TelemetryResult $result): void
    {
        $license = $client->licenses->first();
        $domain = $client->domains->first();

        if (! $license) {
            return;
        }

        TelemetryLog::factory()
            ->count(fake()->numberBetween(3, 10))
            ->create([
                'license_id' => $license->id,
                'domain_used' => $domain?->domain_name,
                'result' => $result,
            ]);
    }

    private function seedAlertLog(Client $client, ?string $state = null, array $overrides = []): void
    {
        $license = $client->licenses->first();

        if (! $license) {
            return;
        }

        $factory = AlertLog::factory();

        if ($state) {
            $factory = $factory->{$state}();
        }

        $factory->create([
            'license_id' => $license->id,
            ...$overrides,
        ]);
    }

    /**
     * Simulasikan histori lengkap reminder H-7, H-3, H-1 sebelum license benar-benar expired.
     */
    private function seedReminderHistory(Client $client): void
    {
        $license = $client->licenses->first();

        if (! $license) {
            return;
        }

        foreach ([7, 3, 1] as $milestone) {
            AlertLog::factory()->create([
                'license_id' => $license->id,
                'milestone_days' => $milestone,
                'sent_at' => $license->expires_at->subDays($milestone),
                'created_at' => $license->expires_at->subDays($milestone),
            ]);
        }
    }
}
