<?php

namespace App\Filament\Actions;

use App\Models\License;
use App\Services\License\LicenseVerificationService;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BulkSuspendLicenseAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulk_suspend';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Suspend Terpilih')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->authorize(fn () => auth()->user()->can('bulkSuspend', License::class))
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan Suspend Massal')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(function (Collection $records, array $data, LicenseVerificationService $service) {
                $count = 0;

                foreach ($records as $record) {
                    if ($record->status->value === 'suspended') {
                        continue;
                    }

                    $service->forceSuspend($record, $data['reason'], Auth::id());
                    $count++;
                }

                Notification::make()
                    ->title("{$count} license berhasil disuspend")
                    ->danger()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
