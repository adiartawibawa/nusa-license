<?php

namespace App\Filament\Actions;

use App\Services\License\LicenseVerificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ReactivateLicenseAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reactivate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Reactivate')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn ($record) => $record->status->value === 'suspended')
            ->authorize(fn ($record) => auth()->user()->can('reactivate', $record))
            ->requiresConfirmation()
            ->modalHeading('Reactivate License')
            ->modalDescription('License akan aktif kembali dan client bisa langsung ping ulang.')
            ->action(function ($record, LicenseVerificationService $service) {
                $service->reactivate($record, Auth::id());

                Notification::make()
                    ->title('License diaktifkan kembali')
                    ->body("License {$record->license_key} sudah aktif.")
                    ->success()
                    ->send();
            });
    }
}
