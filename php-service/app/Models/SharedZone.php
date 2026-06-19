<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedZone extends Model
{
    use HasFactory;

    // Menentukan nama tabel karena menggunakan snake_case jamak custom
    protected $table = 'shared_zones';

    protected $fillable = [
        'name',
        'city_district',
        'coordinates',
        'area_km2',
    ];

    protected $casts = [
        // Karena kolom coordinates di database bertipe JSON, 
        // kita cast ke 'array' agar Laravel otomatis mengubahnya menjadi array PHP saat dibaca
        'coordinates' => 'array', 
        'area_km2' => 'float',
    ];

    /**
     * Relasi One-to-Many ke tabel env_rooms
     * Satu zona bisa memiliki banyak ruangan
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(EnvRoom::class, 'zone_id', 'id');
    }
}