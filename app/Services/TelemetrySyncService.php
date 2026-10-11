<?php

namespace App\Services;

use App\Models\IncidentAlert;
use App\Models\TelemetrySnapshot;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelemetrySyncService
{
    /**
     * Menghubungi API internal website instansi/wilayah dan menyinkronkan data telemetri.
     */
    public function sync(Website $website): array
    {
        $endpoint = rtrim($website->url, '/').'/api/v1/internal/telemetry';
        $token = $website->api_key;

        try {
            $req = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'User-Agent' => 'PantauWeb/1.0 (+https://sinjaikab.go.id; CSIRT Sinjai)',
                    'Accept' => 'application/json',
                ]);

            if ($token) {
                $req = $req->withToken($token);
            }

            $response = $req->get($endpoint);

            if (! $response->successful()) {
                throw new \RuntimeException("Telemetry request failed with status: {$response->status()}");
            }

            $data = $response->json();
            if (($data['status'] ?? '') !== 'success') {
                throw new \RuntimeException('Telemetry response did not indicate success');
            }

            // Simpan Snapshot Telemetri
            TelemetrySnapshot::create([
                'website_id' => $website->id,
                'payload' => $data,
                'recorded_at' => now(),
            ]);

            // Ekstrak Informasi Aplikasi
            $app = $data['app'] ?? [];
            $cmsVersion = $app['cms_version'] ?? null;
            $laravelVersion = $app['laravel_version'] ?? null;
            $phpVersion = $app['php_version'] ?? null;
            $debugMode = (bool) ($app['debug_mode'] ?? false);

            // Ekstrak Informasi Backup
            $backup = $data['backup'] ?? [];
            $latestBackupAt = isset($backup['latest_backup_at']) ? Carbon::parse($backup['latest_backup_at']) : null;
            $latestBackupSize = $backup['latest_backup_size_mb'] ?? null;
            $storageStatus = $backup['storage_status'] ?? [];

            // Evaluasi Status Backup
            $backupStatus = 'success';
            if (! $latestBackupAt) {
                $backupStatus = 'warning';
            } elseif ($latestBackupAt->diffInHours(now()) > 28) {
                $backupStatus = 'overdue';
            }

            // Ekstrak Informasi Aktivitas Konten
            $content = $data['content_activity'] ?? [];
            $latestPostAt = isset($content['latest_post_at']) ? Carbon::parse($content['latest_post_at']) : null;
            $posts30d = (int) ($content['posts_count_30d'] ?? 0);
            $totalPosts = (int) ($content['total_posts'] ?? 0);

            // Ekstrak Informasi Layanan & Penduduk
            $services = $data['services_summary'] ?? [];
            $pendingComplaints = (int) ($services['pending_complaints'] ?? 0);
            $pendingRequests = (int) ($services['pending_service_requests'] ?? 0);
            $totalCitizens = (int) ($services['total_citizens'] ?? 0);
            $totalFamilies = (int) ($services['total_families'] ?? 0);

            // Ekstrak Informasi Kesehatan Sistem
            $health = $data['system_health'] ?? [];
            $lastCronAt = isset($health['last_cron_executed_at']) ? Carbon::parse($health['last_cron_executed_at']) : null;

            // Perbarui data Website
            $website->update([
                'cms_version' => $cmsVersion,
                'laravel_version' => $laravelVersion,
                'php_version' => $phpVersion,
                'debug_mode_detected' => $debugMode,
                'latest_backup_at' => $latestBackupAt,
                'latest_backup_size_mb' => $latestBackupSize,
                'backup_status' => $backupStatus,
                'backup_storage_status' => $storageStatus,
                'latest_post_at' => $latestPostAt,
                'posts_30d' => $posts30d,
                'total_posts' => $totalPosts,
                'pending_complaints' => $pendingComplaints,
                'pending_service_requests' => $pendingRequests,
                'total_citizens' => $totalCitizens,
                'total_families' => $totalFamilies,
                'last_cron_executed_at' => $lastCronAt,
            ]);

            // Deteksi Peringatan: APP_DEBUG aktif di production
            if ($debugMode) {
                IncidentAlert::updateOrCreate(
                    [
                        'website_id' => $website->id,
                        'alert_type' => 'debug_mode',
                        'is_resolved' => false,
                    ],
                    [
                        'severity' => 'critical',
                        'message' => "Kerentanan Keamanan: APP_DEBUG terdeteksi AKTIF di lingkungan production pada portal {$website->name}!",
                        'notified_at' => now(),
                    ]
                );
            }

            // Deteksi Peringatan: Backup overdue
            if ($backupStatus === 'overdue') {
                IncidentAlert::updateOrCreate(
                    [
                        'website_id' => $website->id,
                        'alert_type' => 'backup_failed',
                        'is_resolved' => false,
                    ],
                    [
                        'severity' => 'warning',
                        'message' => "Backup basis data {$website->name} sudah lebih dari 28 jam belum diperbarui (terakhir: {$latestBackupAt})!",
                        'notified_at' => now(),
                    ]
                );
            }

            return [
                'status' => 'success',
                'website' => $website->name,
                'cms_version' => $cmsVersion,
                'backup_status' => $backupStatus,
                'posts_30d' => $posts30d,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'website' => $website->name,
                'error' => $e->getMessage(),
            ];
        }
    }
}
