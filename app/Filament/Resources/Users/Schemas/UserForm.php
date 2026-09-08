<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('role')
                    ->options([
                        UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
                        UserRole::Admin->value => UserRole::Admin->label(),
                        UserRole::Staff->value => UserRole::Staff->label(),
                    ])
                    ->default(UserRole::Staff->value)
                    ->required()
                    ->native(false)
                    // Cegah admin biasa menaikkan role dirinya sendiri/orang lain jadi Super Admin
                    ->disableOptionWhen(
                        fn (string $value) => $value === UserRole::SuperAdmin->value
                            && ! auth()->user()->isSuperAdmin()
                    ),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengubah password.'
                        : null),

                DateTimePicker::make('email_verified_at')
                    ->label('Verified At')
                    ->visibleOn('edit'),
            ]);
    }
}
