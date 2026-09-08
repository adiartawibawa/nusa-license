<?php

namespace App\Filament\Resources\TelemetryLogs\Pages;

use App\Filament\Resources\TelemetryLogs\TelemetryLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTelemetryLogs extends ManageRecords
{
    protected static string $resource = TelemetryLogResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make(),
    //     ];
    // }
}
