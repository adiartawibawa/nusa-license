<?php

namespace App\Filament\Actions;

use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class VerifyDomainAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verify_domain';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Verify Domain')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->visible(fn (Domain $record) => ! $record->is_verified)
            ->authorize(fn (Domain $record) => auth()->user()->can('update', $record->client))
            ->requiresConfirmation()
            ->modalHeading('Verifikasi Domain')
            ->modalDescription(fn (Domain $record) => "Pastikan domain \"{$record->domain_name}\" benar-benar dikendalikan oleh client sebelum verifikasi (cek DNS/ownership terlebih dahulu). Setelah verified, license dengan domain ini bisa langsung ACTIVE.")
            ->modalSubmitActionLabel('Ya, Domain Terverifikasi')
            ->action(function (Domain $record) {
                $record->update([
                    'is_verified' => true,
                    'verified_at' => now(),
                    'verified_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Domain terverifikasi')
                    ->body("{$record->domain_name} sudah diverifikasi oleh ".Auth::user()->name)
                    ->success()
                    ->send();
            });
    }
}
