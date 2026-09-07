<?php

namespace App\Filament\Widgets;

use App\Enums\TelemetryResult;
use App\Models\TelemetryLog;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class TelemetryHeartbeatChart extends ChartWidget
{
    protected ?string $heading = 'Telemetry Heartbeat (7 Hari Terakhir)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    // Filter periode — tersedia sebagai dropdown di pojok kanan widget
    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari Terakhir',
            '14' => '14 Hari Terakhir',
            '30' => '30 Hari Terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 7);

        $rows = Cache::remember("widget:telemetry_heartbeat:{$days}", 60, function () use ($days) {
            return TelemetryLog::query()
                ->selectRaw('DATE(pinged_at) as day, result, count(*) as total')
                ->where('pinged_at', '>=', now()->subDays($days - 1)->startOfDay())
                ->groupBy('day', 'result')
                ->orderBy('day')
                ->get()
                ->toArray();
        });

        $dateRange = collect(range($days - 1, 0))
            ->map(fn ($i) => now()->subDays($i)->toDateString());

        $datasets = [];

        $resultConfig = [
            TelemetryResult::Success->value => ['label' => 'Success', 'color' => '#22c55e'],
            TelemetryResult::RejectedSignature->value => ['label' => 'Rejected: Signature', 'color' => '#ef4444'],
            TelemetryResult::RejectedDomain->value => ['label' => 'Rejected: Domain', 'color' => '#f59e0b'],
            TelemetryResult::RejectedStatus->value => ['label' => 'Rejected: Status', 'color' => '#94a3b8'],
        ];

        foreach ($resultConfig as $resultValue => $config) {
            $dataPerDay = $dateRange->map(function ($date) use ($rows, $resultValue) {
                $match = collect($rows)->first(
                    fn ($row) => $row['day'] === $date && $row['result'] === $resultValue
                );

                return $match['total'] ?? 0;
            });

            // Skip dataset yang seluruhnya 0 — biar chart tidak penuh garis flat tak berguna
            if ($dataPerDay->sum() === 0) {
                continue;
            }

            $datasets[] = [
                'label' => $config['label'],
                'data' => $dataPerDay->values()->toArray(),
                'borderColor' => $config['color'],
                'backgroundColor' => $config['color'].'33', // transparansi untuk fill area
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $dateRange->map(fn ($d) => Carbon::parse($d)->translatedFormat('d M'))->toArray(),
        ];
    }
}
