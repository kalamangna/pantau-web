<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'parent_region',
        'domain',
        'url',
        'api_key',
        'is_active',
        'current_status',
        'last_http_code',
        'last_response_time_ms',
        'last_checked_at',
        'ssl_issuer',
        'ssl_expires_at',
        'ssl_days_left',
        'cms_version',
        'laravel_version',
        'php_version',
        'debug_mode_detected',
        'latest_backup_at',
        'latest_backup_size_mb',
        'backup_status',
        'backup_storage_status',
        'latest_post_at',
        'posts_30d',
        'total_posts',
        'pending_complaints',
        'pending_service_requests',
        'total_citizens',
        'total_families',
        'last_cron_executed_at',
        'last_error_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'debug_mode_detected' => 'boolean',
        'last_checked_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'latest_backup_at' => 'datetime',
        'latest_post_at' => 'datetime',
        'last_cron_executed_at' => 'datetime',
        'backup_storage_status' => 'array',
        'last_response_time_ms' => 'float',
        'latest_backup_size_mb' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Website $website) {
            if (empty($website->slug)) {
                $website->slug = Str::slug($website->name);
            }
        });
    }

    public function uptimeLogs(): HasMany
    {
        return $this->hasMany(UptimeLog::class);
    }

    public function telemetrySnapshots(): HasMany
    {
        return $this->hasMany(TelemetrySnapshot::class);
    }

    public function incidentAlerts(): HasMany
    {
        return $this->hasMany(IncidentAlert::class);
    }
}
