<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'is_online',
        'http_status',
        'response_time_ms',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'response_time_ms' => 'float',
        'checked_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
