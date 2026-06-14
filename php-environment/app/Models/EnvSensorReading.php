<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvSensorReading extends Model
{
    protected $table = 'env_sensor_readings';
    public $timestamps = false; // Tabel ini tidak punya kolom updated_at

    protected $fillable = [
        'zone_id', 'pm25', 'pm10', 'no2', 'co', 'o3', 
        'temperature', 'humidity', 'sensor_id', 'recorded_at', 'created_at'
    ];
}