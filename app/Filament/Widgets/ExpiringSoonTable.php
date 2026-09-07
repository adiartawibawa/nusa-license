<?php

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Filament\Actions\ReactivateLicenseAction;
use App\Filament\Actions\SuspendLicenseAction;
use App\Models\License;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringSoonTable extends TableWidget
{
    protected static ?string $heading = 'License Mendekati Kadaluarsa';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2; // tampil setelah stats overview

    public function table(Table $table): Table
    {
        return $table
            ->query(
                License::query()
                    ->with('client')
                    ->where('status', LicenseStatus::Active)
                    ->whereBetween('expires_at', [now(), now()->addDays(7)])
                    ->orderBy('expires_at')
            )
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('client.phone_number')
                    ->label('Kontak')
                    ->copyable(),
                TextColumn::make('license_key')
                    ->label('License')
                    ->limit(18)
                    ->copyable(),
                TextColumn::make('expires_at')
                    ->label('Berakhir')
                    ->date()
                    ->badge()
                    ->color(function ($record) {
                        $days = now()->diffInHours($record->expires_at, false) / 24;

                        return $days <= 3 ? 'danger' : 'warning';
                    }),
                TextColumn::make('expires_at')
                    ->label('Sisa Waktu')
                    ->state(function ($record) {
                        $days = (int) ceil(now()->diffInHours($record->expires_at, false) / 24);

                        return $days <= 0
                            ? 'Sudah lewat'
                            : "{$days} hari lagi";
                    }),
            ])
            ->recordActions([
                SuspendLicenseAction::make(),
                ReactivateLicenseAction::make(),
            ])
            ->paginated([5, 10, 25])
            ->poll('60s');
    }
}
