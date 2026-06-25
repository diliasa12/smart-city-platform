<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class EnvRoom extends Model
{
    use HasFactory;

    
    protected $table = 'env_rooms';

    protected $fillable = [
        'zone_id',
        'room_name',
        'capacity',
        'device_token',
        'is_active',
    ];

    protected $casts = [
        'zone_id' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    
    public function zone(): BelongsTo
    {
        return $this->belongsTo(SharedZone::class, 'zone_id', 'id');
    }
    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(EnvRoomTelemetryLog::class, 'room_id');
    }
}