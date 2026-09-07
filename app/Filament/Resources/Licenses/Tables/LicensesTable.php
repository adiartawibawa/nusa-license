<?php

namespace App\Filament\Resources\Licenses\Tables;

use App\Filament\Actions\BulkSuspendLicenseAction;
use App\Filament\Actions\ReactivateLicenseAction;
use App\Filament\Actions\SuspendLicenseAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')->searchable()->copyable(),
                TextColumn::make('client.name')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->color())
                    ->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('expires_at')->date()->sortable(),
                TextColumn::make('last_verified_at')->since()->label('Last Ping')->placeholder('Belum pernah'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                SuspendLicenseAction::make(),
                ReactivateLicenseAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkSuspendLicenseAction::make(),
                ]),
            ]);
    }
}
