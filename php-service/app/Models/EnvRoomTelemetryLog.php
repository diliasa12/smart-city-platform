<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvRoomTelemetryLog extends Model
{
    protected $table = 'env_room_telemetry_logs';
    
    public $timestamps = false; // Hanya menggunakan created_at bawaan MySQL

    protected $fillable = [
        'room_id',
        'temperature',
        'humidity',
        'decibel_level',
        'ml_classification_status',
        'predicted_next_busy_hour'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(EnvRoom::class, 'room_id');
    }
}