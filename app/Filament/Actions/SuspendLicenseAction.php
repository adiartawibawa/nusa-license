<?php

namespace App\Filament\Actions;

use App\Services\License\LicenseVerificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SuspendLicenseAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'suspend';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Suspend (Kill-Switch)')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn ($record) => $record->status->value !== 'suspended')
            ->authorize(fn ($record) => auth()->user()->can('suspend', $record))
            ->requiresConfirmation()
            ->modalHeading('Suspend License')
            ->modalDescription('Aplikasi villa client akan langsung terkunci setelah ping berikutnya (maks. 60 detik).')
            ->modalSubmitActionLabel('Ya, Suspend Sekarang')
            ->schema([
                Select::make('suspend_reason_category')
                    ->label('Alasan')
                    ->options([
                        'unpaid_invoice' => 'Tunggakan Pembayaran',
                        'contract_ended' => 'Kontrak Berakhir',
                        'policy_violation' => 'Pelanggaran Kebijakan',
                        'manual_other' => 'Lainnya',
                    ])
                    ->required()
                    ->live(),
                Textarea::make('suspend_reason_note')
                    ->label('Catatan Tambahan')
                    ->visible(fn ($get) => $get('suspend_reason_category') === 'manual_other')
                    ->required(fn ($get) => $get('suspend_reason_category') === 'manual_other')
                    ->maxLength(500),
            ])
            ->action(function ($record, array $data, LicenseVerificationService $service) {
                $reason = $data['suspend_reason_category'] === 'manual_other'
                    ? $data['suspend_reason_note']
                    : $data['suspend_reason_category'];

                $service->forceSuspend($record, $reason, Auth::id());

                Notification::make()
                    ->title('License disuspend')
                    ->body("License {$record->license_key} berhasil dikunci.")
                    ->danger()
                    ->send();
            });
    }
}
