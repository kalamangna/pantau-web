<?php

namespace App\Services;

use App\Models\IncidentAlert;
use App\Models\UptimeLog;
use App\Models\Website;
use App\Notifications\CsirtIncidentNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Throwable;

class UptimeCheckerService
{
    /**
     * Memeriksa ketersediaan HTTP dan masa berlaku sertifikat SSL suatu website instansi/wilayah.
     */
    public function check(Website $website): array
    {
        $startTime = microtime(true);
        $url = $website->url;
        $isOnline = false;
        $httpCode = null;
        $errorMessage = null;

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withoutRedirecting()
                ->withHeaders([
                    'User-Agent' => 'PantauWeb/1.0 (+https://sinjaikab.go.id; CSIRT Sinjai)',
                ])
                ->get($url);

            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
            $httpCode = $response->status();
            $isOnline = ($httpCode >= 200 && $httpCode < 400);

            if (! $isOnline) {
                $errorMessage = "HTTP status returned {$httpCode}";
            }
        } catch (Throwable $e) {
            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
            $isOnline = false;
            $errorMessage = $e->getMessage();
        }

        // Periksa SSL Certificate
        $sslInfo = $this->inspectSslCertificate($website->domain ?: parse_url($url, PHP_URL_HOST));

        // Tentukan status kesehatan saat ini
        $previousStatus = $website->current_status;
        if (! $isOnline) {
            $currentStatus = 'offline';
        } elseif ($responseTimeMs > 4000 || ($sslInfo['days_left'] !== null && $sslInfo['days_left'] <= 7)) {
            $currentStatus = 'degraded';
        } else {
            $currentStatus = 'online';
        }

        // Catat Uptime Log
        UptimeLog::create([
            'website_id' => $website->id,
            'is_online' => $isOnline,
            'http_status' => $httpCode,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $errorMessage,
            'checked_at' => now(),
        ]);

        // Perbarui atribut Website
        $website->update([
            'current_status' => $currentStatus,
            'last_http_code' => $httpCode,
            'last_response_time_ms' => $responseTimeMs,
            'last_checked_at' => now(),
            'ssl_issuer' => $sslInfo['issuer'],
            'ssl_expires_at' => $sslInfo['expires_at'],
            'ssl_days_left' => $sslInfo['days_left'],
            'last_error_message' => $errorMessage,
        ]);

        // Buat alert jika website down
        if ($currentStatus === 'offline' && $previousStatus !== 'offline') {
            $msg = "Portal {$website->name} ({$website->domain}) tidak dapat diakses!\nKode HTTP: {$httpCode}\nError: {$errorMessage}";
            IncidentAlert::create([
                'website_id' => $website->id,
                'alert_type' => 'down',
                'severity' => 'critical',
                'message' => $msg,
                'notified_at' => now(),
            ]);

            try {
                Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
                    ->notify(new CsirtIncidentNotification(
                        title: "🚨 INSIDEN SITUS DOWN: {$website->name}",
                        message: $msg,
                        severity: 'critical',
                        url: $website->url
                    ));
            } catch (Throwable) {
                // Abaikan error koneksi telegram
            }
        } elseif ($currentStatus === 'online' && $previousStatus === 'offline') {
            // Selesaikan insiden down sebelumnya
            IncidentAlert::where('website_id', $website->id)
                ->where('alert_type', 'down')
                ->where('is_resolved', false)
                ->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                ]);

            try {
                Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
                    ->notify(new CsirtIncidentNotification(
                        title: "✅ SITUS KEMBALI NORMAL: {$website->name}",
                        message: "Portal {$website->name} ({$website->domain}) telah pulih kembali dan dapat diakses (Respon: {$responseTimeMs} ms).",
                        severity: 'info',
                        url: $website->url
                    ));
            } catch (Throwable) {
                // Abaikan error koneksi telegram
            }
        }

        // Peringatan jika SSL akan kedaluwarsa (< 14 hari)
        if ($sslInfo['days_left'] !== null && $sslInfo['days_left'] <= 14) {
            $existingAlert = IncidentAlert::where('website_id', $website->id)
                ->where('alert_type', 'ssl_expiring')
                ->where('is_resolved', false)
                ->first();

            if (! $existingAlert) {
                $sslMsg = "Sertifikat SSL {$website->name} ({$website->domain}) akan kedaluwarsa dalam {$sslInfo['days_left']} hari ({$sslInfo['expires_at']})!";
                IncidentAlert::create([
                    'website_id' => $website->id,
                    'alert_type' => 'ssl_expiring',
                    'severity' => $sslInfo['days_left'] <= 3 ? 'critical' : 'warning',
                    'message' => $sslMsg,
                    'notified_at' => now(),
                ]);

                try {
                    Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
                        ->notify(new CsirtIncidentNotification(
                            title: "⚠️ PERINGATAN SSL: {$website->name}",
                            message: $sslMsg,
                            severity: $sslInfo['days_left'] <= 3 ? 'critical' : 'warning',
                            url: $website->url
                        ));
                } catch (Throwable) {
                    // Abaikan error koneksi telegram
                }
            }
        }

        return [
            'website' => $website->name,
            'status' => $currentStatus,
            'http_code' => $httpCode,
            'response_time_ms' => $responseTimeMs,
            'ssl' => $sslInfo,
        ];
    }

    /**
     * Memeriksa informasi sertifikat SSL domain target.
     */
    protected function inspectSslCertificate(string $host): array
    {
        $result = [
            'issuer' => null,
            'expires_at' => null,
            'days_left' => null,
        ];

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                5,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($client) {
                $params = stream_context_get_params($client);
                $cert = $params['options']['ssl']['peer_certificate'] ?? null;

                if ($cert) {
                    $certInfo = openssl_x509_parse($cert);
                    if ($certInfo && isset($certInfo['validTo_time_t'])) {
                        $expiresAt = Carbon::createFromTimestamp($certInfo['validTo_time_t']);
                        $daysLeft = (int) now()->diffInDays($expiresAt, false);
                        $issuer = $certInfo['issuer']['O'] ?? $certInfo['issuer']['CN'] ?? 'Unknown Issuer';

                        $result['issuer'] = (string) $issuer;
                        $result['expires_at'] = $expiresAt;
                        $result['days_left'] = $daysLeft;
                    }
                }
                fclose($client);
            }
        } catch (Throwable) {
            // Abaikan jika handshake gagal
        }

        return $result;
    }
}
