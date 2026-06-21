<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTelemetryLog extends Model
{
    protected $table = 'env_room_telemetry_logs';

    public $timestamps = false; // tabel ini cuma punya created_at, tanpa updated_at

    protected $fillable = [
        'room_id',
        'temperature',
        'humidity',
        'decibel_level',
        'ml_classification_status',
        'predicted_next_busy_hour',
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity'    => 'float',
        'decibel_level' => 'float',
        'predicted_next_busy_hour' => 'integer',
        'created_at'  => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(EnvRoom::class, 'room_id');
    }
}