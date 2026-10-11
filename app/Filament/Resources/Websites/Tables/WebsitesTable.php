<?php

namespace App\Filament\Resources\Websites\Tables;

use App\Models\Website;
use App\Services\TelemetrySyncService;
use App\Services\UptimeCheckerService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebsitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Instansi / Portal')
                    ->description(fn (Website $record): string => $record->domain)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'opd' => 'warning',
                        'kecamatan' => 'primary',
                        'kelurahan' => 'info',
                        'desa' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'opd' => 'OPD',
                        'kecamatan' => 'Kecamatan',
                        'kelurahan' => 'Kelurahan',
                        'desa' => 'Desa',
                        default => strtoupper($state),
                    }),

                TextColumn::make('parent_region')
                    ->label('Wilayah / Induk')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('current_status')
                    ->label('Status Akses')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'online' => 'success',
                        'degraded' => 'warning',
                        'offline' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'online' => 'Online',
                        'degraded' => 'Lambat',
                        'offline' => 'Down / Offline',
                        default => 'Belum Dicek',
                    }),

                TextColumn::make('last_response_time_ms')
                    ->label('Latensi')
                    ->formatStateUsing(fn (?float $state): string => $state ? "{$state} ms" : '-')
                    ->sortable(),

                TextColumn::make('ssl_days_left')
                    ->label('Sisa SSL')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 7 => 'danger',
                        $state <= 14 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state} hari" : '-'),

                TextColumn::make('backup_status')
                    ->label('Backup DB')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'success' => 'Aman',
                        'warning' => 'Perhatian',
                        'overdue' => 'Terlambat (>28j)',
                        default => '-',
                    }),

                TextColumn::make('cms_version')
                    ->label('Versi CMS')
                    ->placeholder('-'),

                TextColumn::make('posts_30d')
                    ->label('Berita (30 Hari)')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('last_checked_at')
                    ->label('Pengecekan Terakhir')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori Instansi')
                    ->options([
                        'opd' => 'OPD (Dinas / Badan)',
                        'kecamatan' => 'Kecamatan',
                        'kelurahan' => 'Kelurahan',
                        'desa' => 'Desa',
                    ]),

                SelectFilter::make('current_status')
                    ->label('Status Akses Web')
                    ->options([
                        'online' => 'Online',
                        'degraded' => 'Lambat',
                        'offline' => 'Down / Offline',
                    ]),

                SelectFilter::make('backup_status')
                    ->label('Status Backup')
                    ->options([
                        'success' => 'Aman',
                        'warning' => 'Peringatan',
                        'overdue' => 'Terlambat',
                    ]),
            ])
            ->recordActions([
                Action::make('check_uptime')
                    ->label('Ping')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('info')
                    ->action(function (Website $record, UptimeCheckerService $checker) {
                        $result = $checker->check($record);
                        Notification::make()
                            ->title("Pengecekan {$record->name}")
                            ->body("Status: {$result['status']} | HTTP {$result['http_code']} ({$result['response_time_ms']} ms)")
                            ->status($result['status'] === 'online' ? 'success' : 'danger')
                            ->send();
                    }),

                Action::make('sync_telemetry')
                    ->label('Sync')
                    ->icon(Heroicon::OutlinedCloudArrowDown)
                    ->color('success')
                    ->action(function (Website $record, TelemetrySyncService $syncer) {
                        $result = $syncer->sync($record);
                        if (($result['status'] ?? '') === 'success') {
                            Notification::make()
                                ->title("Telemetri {$record->name} Disinkronkan")
                                ->body("Versi CMS: {$result['cms_version']} | Backup: {$result['backup_status']}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title("Telemetri {$record->name} Tidak Tersedia")
                                ->body($result['error'] ?? 'Endpoint telemetri tidak ditemukan atau belum dikonfigurasi.')
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('visit')
                    ->label('Buka')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Website $record): string => $record->url)
                    ->openUrlInNewTab(),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
