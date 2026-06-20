<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedZone extends Model
{
    use HasFactory;

   
    protected $table = 'shared_zones';

    protected $fillable = [
        'name',
        'city_district',
        'coordinates',
        'area_km2',
    ];

    protected $casts = [
        'coordinates' => 'array', 
        'area_km2' => 'float',
    ];

    
    public function rooms(): HasMany
    {
        return $this->hasMany(EnvRoom::class, 'zone_id', 'id');
    }
}