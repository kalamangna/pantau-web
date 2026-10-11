<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Dinas Komunikasi, Informatika dan Persandian
            $table->string('slug')->unique();
            $table->enum('category', ['opd', 'kecamatan', 'kelurahan', 'desa'])->default('opd');
            $table->string('parent_region')->nullable(); // Kecamatan (untuk kelurahan/desa) atau Wilayah
            $table->string('domain')->unique(); // diskominfo.sinjaikab.go.id / tompobulu.desa.id
            $table->string('url'); // https://diskominfo.sinjaikab.go.id
            $table->string('api_key')->nullable(); // Bearer token for telemetry (jika mendukung)
            $table->boolean('is_active')->default(true);

            // Real-time Uptime & SSL status
            $table->string('current_status')->default('unknown'); // online, degraded, offline, unknown
            $table->integer('last_http_code')->nullable();
            $table->float('last_response_time_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('ssl_issuer')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->integer('ssl_days_left')->nullable();

            // Telemetry: App & System Info (jika memiliki endpoint telemetri)
            $table->string('cms_version')->nullable();
            $table->string('laravel_version')->nullable();
            $table->string('php_version')->nullable();
            $table->boolean('debug_mode_detected')->nullable();

            // Telemetry: Backup Status
            $table->timestamp('latest_backup_at')->nullable();
            $table->float('latest_backup_size_mb')->nullable();
            $table->string('backup_status')->default('unknown'); // success, warning, overdue, error, unknown
            $table->json('backup_storage_status')->nullable();

            // Telemetry: Content & Service activity
            $table->timestamp('latest_post_at')->nullable();
            $table->integer('posts_30d')->default(0);
            $table->integer('total_posts')->default(0);
            $table->integer('pending_complaints')->default(0);
            $table->integer('pending_service_requests')->default(0);
            $table->integer('total_citizens')->default(0);
            $table->integer('total_families')->default(0);
            $table->timestamp('last_cron_executed_at')->nullable();

            $table->text('last_error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
