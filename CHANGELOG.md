# Changelog - PANTAU WEB

Semua perubahan signifikan pada proyek ini akan didokumentasikan di berkas ini sesuai format [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

## [1.0.0] - 2026-10-11

### Ditambahkan
- **Inisialisasi Sistem Pemantauan Terpusat (PANTAU WEB)**:
  - Dibangun menggunakan **Laravel 12** dan panel admin **Filament v5** dengan tema warna Emerald.
  - Dirancang khusus untuk subdomain pemantauan Pemerintah Kabupaten Sinjai (`pantau.sinjaikab.go.id`).
- **Dukungan Monitoring Multi-Entitas Pemerintah Daerah**:
  - Model `Website` dengan pengelompokan 4 kategori hierarki:
    - **OPD** (Dinas, Badan, RSUD)
    - **Kecamatan** (Kantor Kecamatan)
    - **Kelurahan** (Kantor Kelurahan)
    - **Desa** (Pemerintah Desa)
- **Layanan Pemantauan Uptime & Keamanan SSL**:
  - `UptimeCheckerService` untuk pemeriksaan HTTP status code, latensi akses (ms), dan masa berlaku sertifikat SSL (*SSL Expiry countdown*) melalui soket OpenSSL.
  - Deteksi otomatis situs *down* dan peringatan sertifikat SSL yang mendekati masa kedaluwarsa (< 14 hari).
- **Layanan Telemetri Mendalam (White-box Monitoring)**:
  - `TelemetrySyncService` untuk menarik data status backup basis data Spatie (lokal & Google Drive), versi CMS Desa/Laravel/PHP, keaktifan postingan 30 hari terakhir, layanan warga, dan deteksi kerentanan `APP_DEBUG` aktif di lingkungan production.
  - Peringatan otomatis jika pencadangan database tertunda/terlambat lebih dari 28 jam.
- **Integrasi Peringatan Insiden Siber via Telegram Bot**:
  - Notifikasi Telegram instan (`CsirtIncidentNotification`) dengan footer resmi `CSIRT-SINJAIKAB • Tim Tanggap Insiden Siber Kabupaten Sinjai` dan waktu berformat WITA.
- **Antarmuka Panel Admin Filament**:
  - `WebsiteResource` dengan visualisasi status badge dinamis (*Online*, *Lambat*, *Down*), indikator sisa hari SSL, status backup, serta aksi cepat (*Ping Uptime*, *Sync Telemetry*, *Buka Web*).
  - Filter pencarian berdasarkan Kategori Instansi (OPD, Kecamatan, Kelurahan, Desa), Status Akses, dan Status Backup.
  - Widget ringkasan statistik `StatsOverviewWidget` (Total Domain, Tingkat Ketersediaan, Peringatan Aktif, Latensi).
  - Widget log peringatan terkini `LatestAlertsWidget` dengan tombol *Tandai Selesai*.
- **Otomasi Scheduler & Perintah Artisan**:
  - `php artisan pantau:check-uptime` terjadwal setiap 5 menit.
  - `php artisan pantau:sync-telemetry` terjadwal setiap 2 jam.
- **Pengujian Otomatis (Feature & Unit Tests)**:
  - Pengujian ketersediaan panel admin, verifikasi status HTTP uptime, peringatan downtime, sinkronisasi payload telemetri, dan dispatch notifikasi insiden Telegram.
