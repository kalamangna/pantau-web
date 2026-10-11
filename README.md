# 🌐 PANTAU WEB — Kabupaten Sinjai

Sistem Pemantauan Terpusat Ketersediaan, Kinerja, Keamanan, dan Kebugaran Portal Website Resmi Pemerintah Kabupaten Sinjai (**OPD, Kecamatan, Kelurahan, dan Desa**).

---

## 🚀 Fitur Utama

- **Pemantauan Uptime & Latensi Jaringan**:
  - Pengecekan HTTP status code dan waktu respon (ms) berkala tanpa membebani server target.
  - Deteksi dini *downtime* dengan riwayat log lengkap di basis data.
- **Pemeriksaan Masa Aktif Sertifikat SSL**:
  - Penghitungan mundur masa berlaku sertifikat SSL (*SSL Expiry Tracking*) melalui soket OpenSSL.
  - Peringatan otomatis jika sertifikat SSL mendekati masa kedaluwarsa (< 14 hari).
- **Telemetri Mendalam CMS Desa & Portal**:
  - Penarikan telemetri berbasis API terproteksi Bearer Token.
  - Verifikasi berkala pencadangan database (*Spatie Backup*) lokal maupun Google Drive.
  - Peringatan otomatis jika backup database terlambat lebih dari 28 jam.
  - Pendeteksi kerentanan keamanan jika mode debug (`APP_DEBUG=true`) terdeteksi aktif di production.
  - Pelacakan keaktifan postingan artikel/berita (30 hari terakhir) dan status layanan mandiri warga.
- **Peringatan Insiden Terpusat (Telegram Bot)**:
  - Notifikasi langsung ke kanal tim teknis Diskominfo / CSIRT-Sinjaikab saat terjadi situs *down*, kegagalan backup, atau sertifikat SSL akan kedaluwarsa.
- **Panel Admin Intuitif (Filament v5)**:
  - Pengelompokan 4 tingkatan instansi: **OPD**, **Kecamatan**, **Kelurahan**, dan **Desa**.
  - Aksi cepat: *Ping Uptime Langsung*, *Sinkronisasi Telemetri*, dan *Buka Portal*.
  - Widget ringkasan ketersediaan sistem dan tabel insiden aktif.

---

## 🛠️ Stack Teknologi

- **Framework**: [Laravel 12](https://laravel.com) (PHP 8.2+)
- **Admin Panel**: [Filament v5](https://filamentphp.com)
- **CSS Engine**: [Tailwind CSS v4](https://tailwindcss.com)
- **Database**: SQLite / MySQL / MariaDB / PostgreSQL
- **Notifikasi**: Laravel Notification Channels (Telegram Bot API)

---

## 💻 Panduan Instalasi Lokal

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/kalamangna/pantau-web.git
   cd pantau-web
   ```

2. **Instal Dependensi & Konfigurasi**:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Konfigurasi Environment (`.env`)**:
   Tambahkan kredensial bot Telegram untuk notifikasi peringatan insiden:
   ```env
   TELEGRAM_BOT_TOKEN="your-bot-token"
   TELEGRAM_CHAT_ID="your-chat-or-channel-id"
   ```

4. **Migrasi & Seed Data Awal**:
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Akses panel admin di: `http://localhost:8000/admin`
   - **Email**: `admin@sinjaikab.go.id`
   - **Password**: `password`

---

## ⏰ Pengaturan Cron Job / Penjadwalan

Tambahkan perintah berikut ke dalam crontab server peladen untuk menjalankan pengecekan otomatis:

```bash
* * * * * cd /path/to/pantau-web && php artisan schedule:run >> /dev/null 2>&1
```

Perintah yang berjalan secara otomatis:
- `php artisan pantau:check-uptime`: Memeriksa HTTP status & SSL seluruh website (setiap 5 menit).
- `php artisan pantau:sync-telemetry`: Menarik telemetri mendalam dari website desa/kelurahan (setiap 2 jam).

---

## 🧪 Pengujian Otomatis

Jalankan suite pengujian menggunakan perintah:

```bash
php artisan test
```

---

## 📄 Lisensi

Dikembangkan untuk Pemerintah Kabupaten Sinjai. Lisensi di bawah [MIT License](LICENSE).
