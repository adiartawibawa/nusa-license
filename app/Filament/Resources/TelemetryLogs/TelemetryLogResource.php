<?php

namespace App\Filament\Resources\TelemetryLogs;

use App\Enums\TelemetryResult;
use App\Filament\Resources\TelemetryLogs\Pages\ManageTelemetryLogs;
use App\Models\TelemetryLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TelemetryLogResource extends Resource
{
    protected static ?string $model = TelemetryLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    // Read-only — kill-switch cuma dari LicenseResource/ClientResource
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['license.client']); // hindari N+1
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license.license_key')
                    ->label('License')
                    ->searchable()
                    ->limit(18),
                TextColumn::make('license.client.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('domain_used'),
                TextColumn::make('request_ip')
                    ->label('IP'),
                TextColumn::make('result')
                    ->badge()
                    ->color(fn (TelemetryResult $state) => match ($state) {
                        TelemetryResult::Success => 'success',
                        TelemetryResult::RejectedSignature => 'danger',
                        TelemetryResult::RejectedDomain => 'warning',
                        TelemetryResult::RejectedStatus => 'gray',
                    }),
                TextColumn::make('app_version'),
                TextColumn::make('pinged_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('result')
                    ->options([
                        'success' => 'Success',
                        'rejected_signature' => 'Rejected: Signature',
                        'rejected_domain' => 'Rejected: Domain',
                        'rejected_status' => 'Rejected: Status',
                    ]),
            ])
            ->defaultSort('pinged_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTelemetryLogs::route('/'),
        ];
    }
}
