<?php

namespace App\Filament\Resources\Licenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                TextInput::make('license_key')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Auto-generated saat create'),
                DatePicker::make('issued_at')->required(),
                DatePicker::make('expires_at')->required(),
            ]);
    }
}
