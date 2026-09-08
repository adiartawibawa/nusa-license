<?php

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Models\License;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LicenseStatusOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $statsTtl = config('nusalicense.widget_cache_ttl.stats');

        $counts = Cache::remember('widget:license_status_counts', $statsTtl, function () {
            return License::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->mapWithKeys(fn ($total, $status) => [
                    (is_string($status) ? $status : $status->value) => $total,
                ])
                ->toArray(); // <-- WAJIB: simpan array, bukan Collection
        });

        $expiringSoonCount = Cache::remember('widget:license_expiring_soon', $statsTtl, function () {
            return License::query()
                ->where('status', LicenseStatus::Active)
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(); // int, aman
        });

        $activeCount = $counts[LicenseStatus::Active->value] ?? 0;
        $suspendedCount = $counts[LicenseStatus::Suspended->value] ?? 0;
        $expiredCount = $counts[LicenseStatus::Expired->value] ?? 0;
        $unregisteredCount = $counts[LicenseStatus::UnregisteredDomain->value] ?? 0;

        return [
            Stat::make('Active License', $activeCount)
                ->description('License berjalan normal')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getTrendData(LicenseStatus::Active)),

            Stat::make('Suspended (Kill-Switch)', $suspendedCount)
                ->description('Dikunci manual — tunggakan/pelanggaran')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($suspendedCount > 0 ? 'danger' : 'gray')
                ->chart($this->getTrendData(LicenseStatus::Suspended)),

            Stat::make('Expiring Soon (7 Hari)', $expiringSoonCount)
                ->description('Butuh reminder / follow-up renewal')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringSoonCount > 0 ? 'warning' : 'success'),

            Stat::make('Expired', $expiredCount)
                ->description('Masa berlaku habis, belum renewal')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($expiredCount > 0 ? 'warning' : 'gray'),

            Stat::make('Unregistered Domain', $unregisteredCount)
                ->description('Domain belum diverifikasi admin')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color($unregisteredCount > 0 ? 'danger' : 'gray'),
        ];
    }

    /**
     * Mini sparkline 7 hari terakhir — berapa license dengan status ini per hari
     * (dihitung dari last_status_changed_at, bukan created_at, biar reflect histori berubah status).
     */
    private function getTrendData(LicenseStatus $status): array
    {
        $trendTtl = config('nusalicense.widget_cache_ttl.trend');

        return Cache::remember("widget:license_trend:{$status->value}", $trendTtl, function () use ($status) {
            $raw = License::query()
                ->where('status', $status)
                ->where('last_status_changed_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(last_status_changed_at) as day, count(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day'); // Collection

            $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

            return $days->map(fn ($day) => $raw->get($day, 0))->values()->toArray(); // sudah toArray() di sini, aman
        });
    }

    public function forceSuspend(License $license, string $reason, ?string $adminId = null): void
    {
        DB::transaction(function () use ($license, $reason, $adminId) {
            $license->update([
                'status' => LicenseStatus::Suspended,
                'suspend_reason' => $reason,
                'changed_by' => $adminId,
            ]);
        });

        Cache::forget("license_status:{$license->license_key}");
        $this->forgetWidgetCaches();
    }

    public function reactivate(License $license, ?string $adminId = null): void
    {
        DB::transaction(function () use ($license, $adminId) {
            $license->update([
                'status' => LicenseStatus::Active,
                'suspend_reason' => null,
                'changed_by' => $adminId,
            ]);
        });

        Cache::forget("license_status:{$license->license_key}");
        $this->forgetWidgetCaches();
    }

    /**
     * Invalidate semua cache yang dipakai LicenseStatusOverview widget,
     * supaya dashboard reflect perubahan status instan setelah kill-switch/reactivate.
     */
    private function forgetWidgetCaches(): void
    {
        Cache::forget('widget:license_status_counts');
        Cache::forget('widget:license_expiring_soon');

        foreach (LicenseStatus::cases() as $status) {
            Cache::forget("widget:license_trend:{$status->value}");
        }
    }
}
