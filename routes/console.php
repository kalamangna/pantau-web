<?php

use App\Models\Website;
use App\Services\TelemetrySyncService;
use App\Services\UptimeCheckerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pantau:check-uptime', function (UptimeCheckerService $checker) {
    $this->info('Memulai pengecekan uptime seluruh website OPD, Kecamatan, Kelurahan & Desa...');
    $websites = Website::where('is_active', true)->get();

    foreach ($websites as $website) {
        $result = $checker->check($website);
        $this->line("- [{$website->category}] {$result['website']}: {$result['status']} ({$result['response_time_ms']}ms, HTTP {$result['http_code']})");
    }

    $this->info('Pemeriksaan uptime selesai!');
})->purpose('Cek ketersediaan HTTP dan SSL seluruh domain instansi & wilayah');

Artisan::command('pantau:sync-telemetry', function (TelemetrySyncService $syncer) {
    $this->info('Memulai sinkronisasi telemetri mendalam...');
    $websites = Website::where('is_active', true)->get();

    foreach ($websites as $website) {
        $result = $syncer->sync($website);
        $status = $result['status'] ?? 'unknown';
        $this->line("- [{$website->category}] {$result['website']}: {$status}");
    }

    $this->info('Sinkronisasi telemetri selesai!');
})->purpose('Sinkronisasi data backup, versi CMS, konten, dan status internal instansi/wilayah');

// Penjadwalan berkala
Schedule::command('pantau:check-uptime')->everyFiveMinutes();
Schedule::command('pantau:sync-telemetry')->everyTwoHours();
