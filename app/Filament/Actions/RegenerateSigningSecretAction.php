<?php

namespace App\Filament\Actions;

use App\Models\License;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RegenerateSigningSecretAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'regenerate_secret';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Lihat / Regenerate Secret')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->authorize(fn ($record) => auth()->user()->can('update', $record))
            ->requiresConfirmation()
            ->modalHeading('Signing Secret')
            ->modalDescription('Secret lama akan dinonaktifkan permanen. Aplikasi client WAJIB diupdate dengan secret baru setelah ini, atau verifikasi akan selalu gagal.')
            ->modalSubmitActionLabel('Generate Secret Baru')
            ->action(function (License $record) {
                $newSecret = Str::random(
                    config('nusalicense.verification.signing_secret_length')
                );

                $record->update(['signing_secret' => $newSecret]);

                Cache::forget("license_status:{$record->license_key}");

                // Tampilkan SEKALI di notifikasi — setelah ini tidak bisa dilihat lagi
                // via UI (tetap tersimpan encrypted di DB, hanya tidak diexpose ulang).
                Notification::make()
                    ->title('Secret Baru Digenerate')
                    ->body("Copy sekarang, tidak akan ditampilkan lagi:\n\n{$newSecret}")
                    ->persistent()
                    ->warning()
                    ->send();
            });
    }
}
