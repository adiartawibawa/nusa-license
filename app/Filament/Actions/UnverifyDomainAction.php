<?php

namespace App\Filament\Actions;

use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class UnverifyDomainAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unverify_domain';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Cabut Verifikasi')
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->visible(fn (Domain $record) => $record->is_verified)
            ->authorize(fn (Domain $record) => auth()->user()->can('update', $record->client))
            ->requiresConfirmation()
            ->modalHeading('Cabut Verifikasi Domain')
            ->modalDescription('License yang memakai domain ini akan langsung gagal verifikasi (status UNREGISTERED_DOMAIN) pada ping berikutnya.')
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Domain $record, array $data) {
                $record->update([
                    'is_verified' => false,
                    'verified_at' => null,
                    'verified_by' => null,
                ]);

                // Domain berubah -> status resolve semua license client ini bisa berubah,
                // invalidate cache biar tidak nunggu TTL 60 detik.
                foreach ($record->client->licenses as $license) {
                    Cache::forget("license_status:{$license->license_key}");
                }

                Notification::make()
                    ->title('Verifikasi dicabut')
                    ->body("{$record->domain_name}: {$data['reason']}")
                    ->danger()
                    ->send();
            });
    }
}
