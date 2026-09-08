<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('company_name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('phone_number')
                    ->tel()
                    ->placeholder('+628xxxxxxxxxx')
                    ->helperText('Format E.164, dipakai untuk notifikasi WhatsApp'),
                TextInput::make('pic_name')
                    ->label('Nama PIC'),
                TextInput::make('address')
                    ->label('Address')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('tier')
                    ->options([
                        'standard' => 'Standard',
                        'premium' => 'Premium',
                        'enterprise' => 'Enterprise',
                    ])
                    ->default('standard')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
