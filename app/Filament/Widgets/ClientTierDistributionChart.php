<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class ClientTierDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Tier Client';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $counts = Cache::remember('widget:client_tier_distribution', config('nusalicense.widget_cache_ttl.stats'), function () {
            return Client::selectRaw('tier, count(*) as total')
                ->groupBy('tier')
                ->pluck('total', 'tier')
                ->toArray();
        });

        $tiers = ['standard', 'premium', 'enterprise'];
        $labels = array_map('ucfirst', $tiers);
        $data = array_map(fn ($tier) => $counts[$tier] ?? 0, $tiers);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        '#94a3b8', // standard - slate
                        '#f59e0b', // premium - amber
                        '#22c55e', // enterprise - green
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
