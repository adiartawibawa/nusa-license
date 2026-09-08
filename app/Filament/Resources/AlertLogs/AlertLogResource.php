<?php

namespace App\Filament\Resources\AlertLogs;

use App\Filament\Resources\AlertLogs\Pages\ManageAlertLogs;
use App\Models\AlertLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AlertLogResource extends Resource
{
    protected static ?string $model = AlertLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'License Management';

    public static function canCreate(): bool
    {
        return false; // hanya tercipta lewat command/service
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('license.id')
                    ->label('License'),
                TextEntry::make('channel')
                    ->badge(),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('milestone_days')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('sent_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('error_message')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sent_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license.client.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('license.license_key')
                    ->label('License')
                    ->limit(18)
                    ->copyable(),
                TextColumn::make('channel')
                    ->badge(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'expiry_reminder' => 'warning',
                        'suspended_notice' => 'danger',
                        'expired_notice' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('milestone_days')
                    ->label('H-')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'gray',
                    }),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(40)
                    ->placeholder('-')
                    ->tooltip(fn ($record) => $record->error_message),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['sent' => 'Sent', 'failed' => 'Failed', 'pending' => 'Pending']),
                SelectFilter::make('channel')
                    ->options(['whatsapp' => 'WhatsApp', 'email' => 'Email']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAlertLogs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['license.client']);
    }
}
