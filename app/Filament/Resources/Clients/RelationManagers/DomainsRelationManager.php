<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Actions\UnverifyDomainAction;
use App\Filament\Actions\VerifyDomainAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain_name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Tanpa https:// atau www., cth: villa-sunset.com'),
                TextInput::make('server_ip')
                    ->label('Server IP')
                    ->maxLength(45),
                Toggle::make('is_primary')
                    ->label('Domain Utama'),
                Toggle::make('is_verified')
                    ->label('Terverifikasi')
                    ->helperText('Domain harus terverifikasi agar license bisa aktif'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain_name')
            ->columns([
                TextColumn::make('domain_name')->searchable(),
                TextColumn::make('server_ip')->label('Server IP'),
                IconColumn::make('is_primary')->boolean()->label('Utama'),
                IconColumn::make('is_verified')->boolean()->label('Verified'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                VerifyDomainAction::make(),
                UnverifyDomainAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
