<?php

namespace App\Filament\Widgets;

use App\Models\IncidentAlert;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestAlertsWidget extends BaseWidget
{
    protected static ?string $heading = '🚨 Peringatan Insiden & Kerentanan Terkini (OPD, Kec, Kel & Desa)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                IncidentAlert::query()
                    ->with('website')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('website.name')
                    ->label('Instansi / Wilayah')
                    ->description(fn (IncidentAlert $record): string => strtoupper($record->website?->category ?? ''))
                    ->sortable(),

                TextColumn::make('severity')
                    ->label('Tingkat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('alert_type')
                    ->label('Tipe Insiden')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'down' => 'Website Offline',
                        'ssl_expiring' => 'SSL Hampir Habis',
                        'backup_failed' => 'Backup Terlambat',
                        'debug_mode' => 'APP_DEBUG Aktif',
                        default => $state,
                    }),

                TextColumn::make('message')
                    ->label('Rincian Pesan')
                    ->wrap(),

                TextColumn::make('is_resolved')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Selesai' : 'Belum Ditangani'),

                TextColumn::make('created_at')
                    ->label('Waktu Kejadian')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Tandai Selesai')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (IncidentAlert $record): bool => ! $record->is_resolved)
                    ->action(fn (IncidentAlert $record) => $record->update([
                        'is_resolved' => true,
                        'resolved_at' => now(),
                    ])),
            ]);
    }
}
