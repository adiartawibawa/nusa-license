<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Enums\LicenseStatus;
use App\Filament\Actions\ReactivateLicenseAction;
use App\Filament\Actions\SuspendLicenseAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LicensesRelationManager extends RelationManager
{
    protected static string $relationship = 'licenses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('issued_at')->required()->default(now()),
                DatePicker::make('expires_at')->required()->default(now()->addYear()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('license_key')
            ->columns([
                TextColumn::make('license_key')
                    ->searchable()
                    ->copyable()
                    ->limit(18),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (LicenseStatus $state) => $state->color())
                    ->formatStateUsing(fn (LicenseStatus $state) => $state->label()),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
                TextColumn::make('last_verified_at')
                    ->since()
                    ->label('Last Ping')
                    ->placeholder('Belum pernah'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                SuspendLicenseAction::make(),
                ReactivateLicenseAction::make(),
            ]);
    }
}
