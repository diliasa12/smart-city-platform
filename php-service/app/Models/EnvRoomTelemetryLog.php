<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvRoomTelemetryLog extends Model
{
    protected $table = 'env_room_telemetry_logs';
    
    public $timestamps = false; 

    protected $fillable = [
    'room_id',
    'temperature',
    'humidity',
    'decibel_level',
    'ml_status',                  
    'ml_classification_status',
    'predicted_next_busy_hour',
    'is_anomaly',
];
    protected $casts = [
        'temperature' => 'float',
        'humidity'    => 'float',
        'decibel_level' => 'float',
        'predicted_next_busy_hour' => 'integer',
        'is_anomaly'  => 'boolean',
        'created_at'  => 'datetime',
    ];

    const UPDATED_AT = null;
    public function room(): BelongsTo
    {
        return $this->belongsTo(EnvRoom::class, 'room_id');
    }
}