<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvRoom extends Model
{
    use HasFactory;

    // Menentukan nama tabel karena tidak menggunakan aturan jamak standar Laravel
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

    /**
     * Relasi ke tabel shared_zones (Many-to-One)
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(SharedZone::class, 'zone_id', 'id');
    }
}