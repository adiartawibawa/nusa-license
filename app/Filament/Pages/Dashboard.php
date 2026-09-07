<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClientOverview;
use App\Filament\Widgets\ClientTierDistributionChart;
use App\Filament\Widgets\ExpiringSoonTable;
use App\Filament\Widgets\TelemetryHeartbeatChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Override;

class Dashboard extends BaseDashboard
{
    #[Override]
    public function getWidgets(): array
    {
        return [
            ClientOverview::class,
            ClientTierDistributionChart::class,
            ExpiringSoonTable::class,
            TelemetryHeartbeatChart::class,
        ];
    }
}
