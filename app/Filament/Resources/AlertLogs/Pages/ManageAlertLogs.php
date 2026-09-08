<?php

namespace App\Filament\Resources\AlertLogs\Pages;

use App\Filament\Resources\AlertLogs\AlertLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAlertLogs extends ManageRecords
{
    protected static string $resource = AlertLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
