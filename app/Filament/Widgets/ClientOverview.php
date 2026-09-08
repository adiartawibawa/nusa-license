<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ClientOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0; // tampil paling atas, sebelum license stats

    protected function getStats(): array
    {
        $data = Cache::remember('widget:client_overview', config('nusalicense.widget_cache_ttl.stats'), function () {
            return [
                'total' => Client::count(),
                'active' => Client::where('is_active', true)->count(),
                'no_license' => Client::whereDoesntHave('licenses')->count(),
            ];
        });

        return [
            Stat::make('Total Client', $data['total'])
                ->description($data['active'].' aktif')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Client Tanpa License', $data['no_license'])
                ->description('Belum ada license diterbitkan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($data['no_license'] > 0 ? 'warning' : 'success'),
        ];
    }
}
