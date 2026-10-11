<?php

namespace App\Filament\Widgets;

use App\Models\IncidentAlert;
use App\Models\Website;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $total = Website::where('is_active', true)->count();
        $online = Website::where('is_active', true)->where('current_status', 'online')->count();
        $degraded = Website::where('is_active', true)->where('current_status', 'degraded')->count();
        $offline = Website::where('is_active', true)->where('current_status', 'offline')->count();

        $opdCount = Website::where('is_active', true)->where('category', 'opd')->count();
        $kecCount = Website::where('is_active', true)->where('category', 'kecamatan')->count();
        $kelCount = Website::where('is_active', true)->where('category', 'kelurahan')->count();
        $desaCount = Website::where('is_active', true)->where('category', 'desa')->count();

        $uptimePercentage = $total > 0 ? round(($online / $total) * 100, 1) : 0;

        $unresolvedAlerts = IncidentAlert::where('is_resolved', false)->count();

        $avgLatency = Website::where('is_active', true)
            ->whereNotNull('last_response_time_ms')
            ->avg('last_response_time_ms');

        return [
            Stat::make('Total Portal Dipantau', "{$total} Domain")
                ->description("{$opdCount} OPD | {$kecCount} Kec | {$kelCount} Kel | {$desaCount} Desa")
                ->descriptionIcon(Heroicon::OutlinedGlobeAlt)
                ->color('primary'),

            Stat::make('Status Ketersediaan', "{$uptimePercentage}% Online")
                ->description("{$online} Online, {$degraded} Lambat, {$offline} Down")
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color($offline > 0 ? 'danger' : ($degraded > 0 ? 'warning' : 'success')),

            Stat::make('Peringatan & Insiden Aktif', "{$unresolvedAlerts} Peringatan")
                ->description($unresolvedAlerts === 0 ? 'Seluruh Sistem Stabil & Terkendali' : 'Membutuhkan Respons Tim CSIRT')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($unresolvedAlerts > 0 ? 'danger' : 'success'),

            Stat::make('Rata-rata Kecepatan Respon', ($avgLatency ? round($avgLatency, 1).' ms' : '-'))
                ->description('Latensi jaringan portal pemkab')
                ->descriptionIcon(Heroicon::OutlinedBolt)
                ->color($avgLatency && $avgLatency > 2000 ? 'warning' : 'success'),
        ];
    }
}
